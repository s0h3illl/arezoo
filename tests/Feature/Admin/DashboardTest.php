<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia;

test('an admin reaches the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('admin/Dashboard')
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give, so the panel leaves
| no trace for anyone who can't use it.
*/
test('a signed-in user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.dashboard'))->assertNotFound();
});

test('a signed-out visitor gets a 404', function () {
    $this->get(route('admin.dashboard'))->assertNotFound();
});

test('an admin sees three platform totals and the moment they were calculated', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $user->id]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $user->id,
        'amount' => 120_000,
    ]);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/Dashboard')
            ->where('snapshot.users_count', 1)
            ->where('snapshot.wishes_count', 1)
            ->where('snapshot.raised_amount', 120_000)
            ->has('snapshot.computed_at')
    );
});

/*
| Blocking is a moderation decision, not a deletion (see CONTEXT.md's Blocked
| entry), so the dashboard's users total must land on the exact figure the
| users list reports.
*/
test('the users total excludes admins, includes blocked users, and matches the users list total', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->admin()->create();
    User::factory()->create();
    User::factory()->blocked()->create();

    $dashboard = $this->get(route('admin.dashboard'));
    $usersList = $this->get(route('admin.users.index'));

    $dashboard->assertInertia(
        fn (AssertableInertia $page) => $page->where('snapshot.users_count', 2)
    );
    $usersList->assertInertia(
        fn (AssertableInertia $page) => $page->where('users.meta.total', 2)
    );
});

test('the wishes total counts every wish, including those belonging to a blocked user', function () {
    $this->actingAs(User::factory()->admin()->create());
    $blocked = User::factory()->blocked()->create();
    Wish::factory()->create(['user_id' => $blocked->id]);
    Wish::factory()->count(2)->create();

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('snapshot.wishes_count', 3)
    );
});

test('the raised total counts only paid contributions, a pending one does not move it', function () {
    $this->actingAs(User::factory()->admin()->create());
    Contribution::factory()->paid()->create(['amount' => 75_000]);
    Contribution::factory()->pending()->create(['amount' => 900_000]);

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('snapshot.raised_amount', 75_000)
    );
});
