<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

test('a card sends a reader to the wish it draws', function (string $selector) {
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'sara']);
    $wish = Wish::factory()->create([
        'user_id' => $sara->id,
        'title' => 'wish-title',
        'thumbnail' => null,
    ]);

    visit(route('profile', 'sara', absolute: false))
        ->click($selector)
        ->assertPathIs(route('wishes.show', $wish, absolute: false));
})->with([
    'its cover' => '.wish-cover-placeholder',
    'its title' => 'wish-title',
]);

test('the contributor list, shows a named row and a masked one without javascript errors', function () {
    $wish = Wish::factory()->create(['title' => 'دوچرخه', 'price' => 1_000_000]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => User::factory()->create(['name' => 'John Doe'])->id,
        'visibility' => ContributionVisibility::Public,
        'amount' => 300_000,
    ]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'visibility' => ContributionVisibility::Hidden,
        'amount' => 200_000,
    ]);

    visit(route('wishes.show', $wish, absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertSee('John Doe')
        ->assertSee(__('contributions.anonymous'));
});

test('the contributor list tells a deleted contributor apart from a masked one', function () {
    $wish = Wish::factory()->create(['title' => 'دوچرخه', 'price' => 1_000_000]);
    $reza = User::factory()->create(['name' => 'Reza Ahmadi']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $reza->id,
        'visibility' => ContributionVisibility::Hidden,
        'amount' => 300_000,
    ]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'visibility' => ContributionVisibility::Hidden,
        'amount' => 200_000,
    ]);

    $reza->delete();

    visit(route('wishes.show', $wish, absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertSee(__('contributions.deleted'))
        ->assertSee(__('contributions.anonymous'))
        ->assertDontSee('Reza Ahmadi');
});

test('the header carries the way back to the owner profile', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    visit(route('wishes.show', $wish, absolute: false))
        ->click('برگشت به پروفایل')
        ->assertPathIs(route('profile', 'sara', absolute: false));
});

it('shows the purchase link', function () {
    $wish = Wish::factory()->create(['purchase_link' => 'https://example.com/bike']);

    visit(route('wishes.show', $wish, absolute: false))
        ->assertSee('لینک خرید محصول')
        ->assertSee('example.com');
});

it('does not show the purchase link when it is null', function () {
    $wish = Wish::factory()->create(['purchase_link' => null]);

    visit(route('wishes.show', $wish, absolute: false))
        ->assertDontSee('لینک خرید محصول');
});
