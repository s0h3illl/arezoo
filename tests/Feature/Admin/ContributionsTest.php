<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('an admin sees every contribution', function () {
    $this->actingAs(User::factory()->admin()->create());
    Contribution::factory()->count(3)->paid()->create();
    Contribution::factory()->pending()->create();

    $this->get(route('admin.contributions.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/contributions/Index')
            ->has('contributions.data', 4)
    );
});

test('the list is paginated', function () {
    $this->actingAs(User::factory()->admin()->create());
    Contribution::factory()->count(25)->paid()->create();

    $this->get(route('admin.contributions.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('contributions.data', 20)
            ->where('contributions.meta.total', 25)
    );

    $this->get(route('admin.contributions.index', ['page' => 2]))->assertInertia(
        fn (AssertableInertia $page) => $page->has('contributions.data', 5)
    );
});

test('the list shows newest contributions first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $older = Contribution::factory()->paid()->create();
    $newer = Contribution::factory()->paid()->create();

    $this->get(route('admin.contributions.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.id', $newer->id)
            ->where('contributions.data.1.id', $older->id)
    );
});

/*
| Visibility governs what a user sees, never what moderation can see — a
| hidden or owner-only contribution's contributor is still surfaced here.
*/
test('a contribution still shows its contributor to the admin, whatever its visibility', function (ContributionVisibility $visibility) {
    $this->actingAs(User::factory()->admin()->create());
    $contributor = User::factory()->create(['name' => 'سارا احمدی']);

    Contribution::factory()
        ->for($contributor, 'contributor')
        ->paid()
        ->create(['visibility' => $visibility]);

    $this->get(route('admin.contributions.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.contributor.name', 'سارا احمدی')
    );
})->with([
    'hidden' => ContributionVisibility::Hidden,
    'owner-only' => ContributionVisibility::OwnerOnly,
]);

/*
| Both identifiers, so a row here can be matched against the gateway's own
| dashboard without a second lookup.
*/
test('a contribution shows the gateway attempt behind it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $contribution = Contribution::factory()->paid()->create();

    $this->get(route('admin.contributions.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.payment.id', $contribution->payment->id)
            ->where('contributions.data.0.payment.transaction_id', $contribution->payment->transaction_id)
            ->where('contributions.data.0.payment.reference_id', $contribution->payment->reference_id)
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give.
*/
test('an authenticated user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.contributions.index'))->assertNotFound();
});

test('a guest visitor gets a 404', function () {
    $this->get(route('admin.contributions.index'))->assertNotFound();
});
