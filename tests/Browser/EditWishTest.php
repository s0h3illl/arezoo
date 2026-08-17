<?php

use App\Models\User;
use App\Models\Wish;

test('an owner can edit their own wish', function () {
    $wish = ownWish(['title' => 'دوچرخه', 'price' => 500_000]);

    visit(route('profile', 'sara', absolute: false))
        ->click('@edit')
        ->fill('title', 'دوچرخه‌ی کوهستان')
        ->click('@save')
        ->assertSee('دوچرخه‌ی کوهستان');

    expect($wish->fresh()->title)->toBe('دوچرخه‌ی کوهستان');
});

test('the edit dialog shows the wish data', function () {
    ownWish([
        'title' => 'دوچرخه',
        'description' => 'برای رفتن به کوه',
        'purchase_link' => 'https://example.com/bike',
        'price' => 500_000,
    ]);

    visit(route('profile', 'sara', absolute: false))
        ->click('@edit')
        ->assertValue('title', 'دوچرخه')
        ->assertValue('description', 'برای رفتن به کوه')
        ->assertValue('purchase_link', 'https://example.com/bike')
        ->assertValue('price', '500000');
});

test('the cover can be taken away, and the card falls back to its placeholder', function () {
    $wish = ownWish(['title' => 'دوچرخه']);

    visit(route('profile', 'sara', absolute: false))
        ->click('@edit')
        ->click('@remove-thumbnail')
        ->click('@save')
        ->assertPresent('.wish-cover-placeholder');

    expect($wish->fresh()->thumbnail)->toBeNull();
});

it('only the owner can edit the wishes', function (bool $signedIn) {
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);
    Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه']);

    if ($signedIn) {
        test()->actingAs(User::factory()->create(['username' => 'reza']));
    }

    visit(route('profile', 'sara', absolute: false))
        ->assertSee('دوچرخه')
        ->assertNotPresent('@edit');
})->with([
    'a signed-in visitor' => true,
    'a guest' => false,
]);

function ownWish(array $attributes = []): Wish
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    test()->actingAs($sara);

    return Wish::factory()->create([...$attributes, 'user_id' => $sara->id]);
}
