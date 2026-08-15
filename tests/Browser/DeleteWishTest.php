<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

/*
| The controls are found by `data-test`, which Pest reaches with a leading `@`.
| The copy on them is Persian and liable to change, and a button that lost its
| word for the icon alone would make `assertDontSee('حذف')` pass whether or not
| the button was there — an absence assertion that had quietly stopped asserting
| anything. Titles below are still matched as text: those are data the test made,
| not wording anyone is free to reword.
*/

test('an owner deletes a wish, and the card goes with it', function () {
    $sara = signInAsWishOwner();
    Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه‌ی کوهستان']);

    visit(route('profile', 'sara', absolute: false))
        ->click('@delete')
        ->click('@delete-confirm')
        ->assertDontSee('دوچرخه‌ی کوهستان');

    expect(Wish::count())->toBe(0)
        ->and(Wish::withTrashed()->count())->toBe(1);
});

test('backing out of the confirmation leaves the wish alone', function () {
    $sara = signInAsWishOwner();
    Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه‌ی کوهستان']);

    visit(route('profile', 'sara', absolute: false))
        ->click('@delete')
        ->click('@delete-cancel')
        ->assertMissing('@delete-confirm');

    /*
     * Read back over a fresh request rather than off the page that was already
     * open. `assertSee` on the card would pass the instant it ran — the card
     * never went anywhere — so a cancel that quietly fired the delete anyway
     * would sail through it. Asking the server again cannot be fooled that way.
     */
    visit(route('profile', 'sara', absolute: false))
        ->assertSee('دوچرخه‌ی کوهستان');

    expect(Wish::count())->toBe(1);
});

test('the money given to a deleted wish stays where it was', function () {
    $sara = signInAsWishOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه‌ی کوهستان']);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 250_000]);

    visit(route('profile', 'sara', absolute: false))
        ->click('@delete')
        ->click('@delete-confirm')
        ->assertDontSee('دوچرخه‌ی کوهستان');

    expect((int) Contribution::query()->paid()->sum('amount'))->toBe(250_000);
});

/*
| Nobody but the owner is offered the control. The route refuses them anyway, so
| this is about not showing someone a button that could only ever tell them no.
*/
it('offers no way to delete to anyone but the owner', function (bool $signedIn) {
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);
    Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه‌ی کوهستان']);

    if ($signedIn) {
        test()->actingAs(User::factory()->create(['username' => 'reza']));
    }

    visit(route('profile', 'sara', absolute: false))
        ->assertSee('دوچرخه‌ی کوهستان')
        ->assertNotPresent('@delete');
})->with([
    'a signed-in visitor' => true,
    'a guest' => false,
]);

/**
 * Sign in as the owner of the profile these tests read.
 *
 * Named apart from the helpers in `WishTest` and `AddWishTest`: Pest loads every
 * test file into one process, so a repeated global helper would be a
 * redeclaration fatal rather than a shadow.
 */
function signInAsWishOwner(): User
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    test()->actingAs($sara);

    return $sara;
}
