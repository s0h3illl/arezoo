<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia;

test('a guest, a signed-in visitor and the owner all read a wish page', function (?string $reader) {
    $owner = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $owner->id]);

    if ($reader === 'owner') {
        $this->actingAs($owner);
    } elseif ($reader === 'visitor') {
        $this->actingAs(User::factory()->create());
    }

    $this->get(route('wishes.show', $wish))->assertOk();
})->with([
    'a guest' => null,
    'a signed-in visitor' => 'visitor',
    'the owner' => 'owner',
]);

test('a soft-deleted wish returns 404', function () {
    $wish = Wish::factory()->create();
    $wish->delete();

    $this->get(route('wishes.show', $wish))->assertNotFound();
});

test('a wish owned by a blocked user returns 404', function () {
    $owner = User::factory()->blocked()->create();
    $wish = Wish::factory()->create(['user_id' => $owner->id]);

    $this->get(route('wishes.show', $wish))->assertNotFound();
});

test('a hidden contribution carries the masked name and its real amount', function () {
    $wish = Wish::factory()->create();
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'visibility' => ContributionVisibility::Hidden,
        'amount' => 120_000,
    ]);

    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'contributions.data.0',
            fn (AssertableInertia $row) => $row
                ->where('state', 'anonymous')
                ->where('contributor.name', __('contributions.anonymous'))
                ->where('contributor.avatar', null)
                ->missing('contributor.id')
                ->where('amount', 120_000)
                ->has('settled_at')
                ->etc()
        )
    );
});

test('a contribution whose contributor deleted their account reads as deleted, at every visibility', function (ContributionVisibility $visibility) {
    $wish = Wish::factory()->create();
    $reza = User::factory()->create(['name' => 'رضا احمدی']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $reza->id,
        'visibility' => $visibility,
        'amount' => 120_000,
    ]);

    $reza->delete();

    $this->actingAs($wish->owner);
    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'contributions.data.0',
            fn (AssertableInertia $row) => $row
                ->where('state', 'deleted')
                ->where('contributor.name', __('contributions.deleted'))
                ->where('contributor.avatar', null)
                ->missing('contributor.id')
                ->where('amount', 120_000)
                ->etc()
        )
    );
})->with([
    'public' => ContributionVisibility::Public,
    'owner-only' => ContributionVisibility::OwnerOnly,
    'hidden' => ContributionVisibility::Hidden,
]);

test('deleted and anonymous are different words', function () {
    expect(__('contributions.deleted'))->not->toBe(__('contributions.anonymous'));
});

test('a visible contribution carries its contributor', function () {
    $wish = Wish::factory()->create();
    $contributor = User::factory()->create(['name' => 'John Doe']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $contributor->id,
        'visibility' => ContributionVisibility::Public,
    ]);

    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'contributions.data.0',
            fn (AssertableInertia $row) => $row
                ->where('state', 'visible')
                ->where('contributor.name', 'John Doe')
                ->etc()
        )
    );
});

test('an owner-visibility contribution is visible to the owner and anonymous to everyone else', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $contributor = User::factory()->create(['name' => 'رضا احمدی']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $contributor->id,
        'visibility' => ContributionVisibility::OwnerOnly,
    ]);

    $this->actingAs($sara);
    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->where('contributions.data.0.contributor.name', 'رضا احمدی')
    );

    $this->actingAs(User::factory()->create());
    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.state', 'anonymous')
            ->where('contributions.data.0.contributor.name', __('contributions.anonymous'))
    );

    auth()->logout();
    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.state', 'anonymous')
            ->where('contributions.data.0.contributor.name', __('contributions.anonymous'))
    );
});

it('shows total of paid items regardless of visibility', function () {
    $wish = Wish::factory()->create();
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::Public]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::Hidden]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::OwnerOnly]);
    Contribution::factory()->pending()->create(['wish_id' => $wish->id]);

    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->where('contributions.meta.total', 3)
    );
});
