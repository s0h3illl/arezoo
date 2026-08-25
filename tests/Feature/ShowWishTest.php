<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia;

test('users can visit a wish detail apge', function () {
    $wish = Wish::factory()->create();

    $this->get(route('wishes.show', $wish))->assertOk();
});

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

test('an anonymous contribution carries the masked name in place of its contributor', function () {
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
                ->where('name', __('contributions.anonymous'))
                ->where('avatar', null)
                ->where('amount', 120_000)
                ->has('settled_at')
                ->etc()
        )
    );
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
                ->where('name', 'John Doe')
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
        fn (AssertableInertia $page) => $page->where('contributions.data.0.name', 'رضا احمدی')
    );

    $this->actingAs(User::factory()->create());
    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->where('contributions.data.0.name', __('contributions.anonymous'))
    );
});

test('contributions arrive ordered by settled_at descending', function () {
    $wish = Wish::factory()->create();
    $oldest = Contribution::factory()->settledAt(now()->subDays(2))->create(['wish_id' => $wish->id]);
    $newest = Contribution::factory()->settledAt(now())->create(['wish_id' => $wish->id]);
    $middle = Contribution::factory()->settledAt(now()->subDay())->create(['wish_id' => $wish->id]);

    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('contributions.data.0.id', $newest->id)
            ->where('contributions.data.1.id', $middle->id)
            ->where('contributions.data.2.id', $oldest->id)
    );
});

it('shows total of items regardless of visibility', function () {
    $wish = Wish::factory()->create();
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::Public]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::Hidden]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'visibility' => ContributionVisibility::OwnerOnly]);
    Contribution::factory()->pending()->create(['wish_id' => $wish->id]);

    $this->get(route('wishes.show', $wish))->assertInertia(
        fn (AssertableInertia $page) => $page->where('contributions.meta.total', 3)
    );
});
