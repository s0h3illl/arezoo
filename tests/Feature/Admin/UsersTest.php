<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

/*
| The list is the platform's users, which an admin is not one of. Staff accounts
| are managed out of band, the same way admin status is granted out of band.
*/
test('an admin sees every user other than an admin', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'مدیر']));
    User::factory()->admin()->create(['name' => 'ناظر']);
    User::factory()->create(['name' => 'سارا']);
    User::factory()->unverified()->create(['name' => 'رضا']);
    User::factory()->blocked()->create(['name' => 'مریم']);

    $response = $this->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 3)
            ->where('users.meta.total', 3)
    );
});

test('an admin is unfindable by a search for their name or email', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->admin()->create(['name' => 'ناظر', 'email' => 'nazer@example.com']);

    $this->get(route('admin.users.index', ['search' => 'ناظر']))->assertInertia(
        fn (AssertableInertia $page) => $page->has('users.data', 0)
    );

    $this->get(route('admin.users.index', ['search' => 'nazer@example.com']))->assertInertia(
        fn (AssertableInertia $page) => $page->has('users.data', 0)
    );
});

/*
| Blocking is a moderation decision, not a deletion, so a blocked user is still
| one of the platform's users and still counted.
*/
test('the total counts every user who is not an admin, blocked ones included', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->admin()->create();
    User::factory()->create();
    User::factory()->blocked()->create();

    $this->get(route('admin.users.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('users.meta.total', 2)
    );
});

test('the list is paginated', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->count(25)->create();

    $this->get(route('admin.users.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 20)
            ->where('users.meta.total', 25)
    );

    $this->get(route('admin.users.index', ['page' => 2]))->assertInertia(
        fn (AssertableInertia $page) => $page->has('users.data', 5)
    );
});

test('searching by name finds a user, on a partial term', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'مدیر']));
    User::factory()->create(['name' => 'سارا احمدی']);
    User::factory()->create(['name' => 'رضا کریمی']);

    $this->get(route('admin.users.index', ['search' => 'احمد']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'سارا احمدی')
    );
});

test('searching by email finds a user, on a partial term', function () {
    $this->actingAs(User::factory()->admin()->create(['email' => 'admin@example.com']));
    User::factory()->create(['name' => 'سارا', 'email' => 'sara@example.com']);
    User::factory()->create(['name' => 'رضا', 'email' => 'reza@example.com']);

    $this->get(route('admin.users.index', ['search' => 'ara@exam']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'سارا')
    );
});

/*
| A bare `%` reaches the database as a LIKE wildcard unless it is escaped, which
| would turn the narrowest possible search into "show me everyone".
*/
test('a wildcard in the search term is searched for, not obeyed', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['name' => 'تخفیف ۱۰۰% ویژه']);
    User::factory()->count(3)->create();

    $this->get(route('admin.users.index', ['search' => '%']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'تخفیف ۱۰۰% ویژه')
    );
});

test('a row shows the name, email, verified state, and blocked state', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->unverified()->blocked()->create([
        'name' => 'سارا',
        'email' => 'sara@example.com',
    ]);

    $this->get(route('admin.users.index', ['search' => 'sara@example.com']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('users.data.0.name', 'سارا')
            ->where('users.data.0.email', 'sara@example.com')
            ->where('users.data.0.email_verified_at', null)
            ->where('users.data.0.is_blocked', true)
    );

    User::factory()->create(['email' => 'reza@example.com']);

    $this->get(route('admin.users.index', ['search' => 'reza@example.com']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->whereNot('users.data.0.email_verified_at', null)
            ->where('users.data.0.is_blocked', false)
    );
});

/*
| Not a leak — the panel is admin-only. It is a promise that the serialised shape
| is deliberate, so a column added to `users` later does not silently start
| riding along to the frontend.
|
| `created_at` is here for the detail view, which shows a registration date the
| list does not; one resource serves both screens, and each reads what it needs.
*/
test('a user carries nothing beyond what the panel serialises', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create();

    $this->get(route('admin.users.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'users.data.0',
            fn (AssertableInertia $row) => $row
                ->hasAll('id', 'name', 'email', 'email_verified_at', 'is_blocked', 'created_at')
        )
    );
});

/*
| The term has to be carried by the pagination links themselves. If page two were
| a bare `?page=2`, an admin who searched and paged would be looking at the whole
| platform while believing they were still looking at their search.
*/
test('a search term survives pagination', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->count(25)->create(['name' => 'سارا احمدی']);

    $response = $this->get(route('admin.users.index', ['search' => 'سارا']));

    $nextPage = $response->viewData('page')['props']['users']['links']['next'];

    expect($nextPage)->toContain('search=')->toContain('page=2');

    $this->get($nextPage)->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 5)
            ->where('users.meta.total', 25)
            ->where('filters.search', 'سارا')
    );
});

test('a search that matches nothing returns an empty page rather than everyone', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->count(3)->create();

    $this->get(route('admin.users.index', ['search' => 'هیچ‌کس']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('users.data', 0)
            ->where('users.meta.total', 0)
            ->where('filters.search', 'هیچ‌کس')
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give.
*/
test('a signed-in user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.users.index'))->assertNotFound();
});

test('a signed-out visitor gets a 404', function () {
    $this->get(route('admin.users.index'))->assertNotFound();
});
