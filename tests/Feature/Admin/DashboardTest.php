<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Model;
use Inertia\Testing\AssertableInertia;

/*
| Forbidding lazy loading sets a static on the model and nothing in the framework
| puts it back, so one test that turns it on would turn it on for every test that
| runs after it — anywhere in the suite. Restoring the default here is what keeps
| that assertion local to the test that wants it, pass or fail.
*/
afterEach(fn () => Model::preventLazyLoading(false));

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

test('an admin sees the five newest wishes, newest first', function () {
    $this->actingAs(User::factory()->admin()->create());

    /*
     * Six, so the cap has something to cut, and created oldest-first so the
     * order the table must produce is the reverse of the order they were made
     * in — a table that simply forgot to sort would still read plausibly.
     */
    $titles = ['یک', 'دو', 'سه', 'چهار', 'پنج', 'شش'];

    foreach ($titles as $daysAgo => $title) {
        Wish::factory()->create([
            'title' => $title,
            'created_at' => now()->subDays(count($titles) - $daysAgo),
        ]);
    }

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('latest_wishes', 5)
            ->where('latest_wishes.0.title', 'شش')
            ->where('latest_wishes.1.title', 'پنج')
            ->where('latest_wishes.2.title', 'چهار')
            ->where('latest_wishes.3.title', 'سه')
            ->where('latest_wishes.4.title', 'دو')
    );
});

/*
| Wishes made in the same instant — a seeder's batch, or two people publishing at
| once — would otherwise come back in whatever order the database felt like, so
| the same five rows could reorder between two refreshes with nothing changed.
*/
test('wishes published in the same moment still order predictably, newest identifier first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $moment = now()->subHour();
    $earlier = Wish::factory()->create(['created_at' => $moment]);
    $later = Wish::factory()->create(['created_at' => $moment]);

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('latest_wishes.0.id', $later->id)
            ->where('latest_wishes.1.id', $earlier->id)
    );
});

test('a wish row carries its title, its owner name, its price, and when it was published', function () {
    $this->actingAs(User::factory()->admin()->create());
    $owner = User::factory()->create(['name' => 'سارا احمدی']);
    $wish = Wish::factory()->create([
        'user_id' => $owner->id,
        'title' => 'دوچرخه‌ی کوهستان',
        'price' => 3_200_000,
    ]);

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'latest_wishes.0',
            fn (AssertableInertia $row) => $row
                ->where('id', $wish->id)
                ->where('title', 'دوچرخه‌ی کوهستان')
                ->where('owner.id', $owner->id)
                ->where('owner.name', 'سارا احمدی')
                ->where('price', 3_200_000)
                ->where('created_at', $wish->created_at?->toJSON())
        )
    );
});

/*
| Not a leak — the panel is admin-only. It is a promise that the shape is
| deliberate: the amount this wish has received is left out on purpose, since a
| per-wish aggregate would cost a query per row for a column the glance does not
| need, and a description or a purchase link should not ride along either.
*/
test('a wish carries nothing beyond what the dashboard serialises', function () {
    $this->actingAs(User::factory()->admin()->create());
    Wish::factory()->create();

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'latest_wishes.0',
            fn (AssertableInertia $row) => $row->hasAll('id', 'title', 'owner', 'price', 'created_at')
        )
    );
});

/*
| The content most likely to need review is not the content hidden from the
| person doing the reviewing. Blocking bars a user from the app; it does not
| confiscate what they published.
*/
test('a wish belonging to a blocked user appears in the table', function () {
    $this->actingAs(User::factory()->admin()->create());
    $blocked = User::factory()->blocked()->create(['name' => 'رضا کریمی']);
    Wish::factory()->create(['user_id' => $blocked->id]);

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('latest_wishes', 1)
            ->where('latest_wishes.0.owner.name', 'رضا کریمی')
    );
});

/*
| The one part of this screen that is certainly current. The totals above it may
| be a day old; a wish published since they were taken still has to appear here,
| which is what makes the stale tiles tolerable.
*/
test('the table reflects a wish published since the totals were calculated', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('snapshot.wishes_count', 0)
            ->has('latest_wishes', 0)
    );

    Wish::factory()->create(['title' => 'کتاب‌های سال آخر']);

    $this->get(route('admin.dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('snapshot.wishes_count', 0)
            ->has('latest_wishes', 1)
            ->where('latest_wishes.0.title', 'کتاب‌های سال آخر')
    );
});

/*
| Naming the owner in every row must not cost a query per row. Forbidding lazy
| loading turns the N+1 into a failure rather than a slow page, so the assertion
| is the request succeeding at all.
*/
test('naming each wish owner does not cost a query per row', function () {
    Model::preventLazyLoading();

    $this->actingAs(User::factory()->admin()->create());
    Wish::factory()->count(5)->create();

    $this->get(route('admin.dashboard'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->has('latest_wishes', 5)
    );
});
