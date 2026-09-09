<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

/*
| Deleting a wish is the owner curating their own profile: there is no rule about
| contributions standing in the way, and any wish may go in any condition. What
| must not go with it is the money. Balances and withdrawals are derived from the
| contribution rows themselves (ADR-0005), so the cases below are as much about
| what survives a delete as about what disappears.
*/

test('an owner deletes their own wish', function () {
    $sara = signInAsOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $response = $this->delete(route('wishes.destroy', $wish));

    $response->assertRedirect(route('profile', 'sara'));
    expect($wish->fresh()->trashed())->toBeTrue();
});

test('a wish people have already paid towards deletes just as freely', function () {
    $sara = signInAsOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->count(2)->create(['wish_id' => $wish->id]);

    $this->delete(route('wishes.destroy', $wish))->assertRedirect(route('profile', 'sara'));

    expect($wish->fresh()->trashed())->toBeTrue();
});

/*
| The heart of the ticket. A cascade here would silently shrink money an owner had
| already earned and was entitled to withdraw, so the rows are counted by hand
| rather than trusted to have survived.
*/
test('every contribution to a deleted wish still exists', function () {
    $sara = signInAsOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 120_000]);
    Contribution::factory()->pending()->create(['wish_id' => $wish->id, 'amount' => 80_000]);

    $this->delete(route('wishes.destroy', $wish));

    expect(Contribution::where('wish_id', $wish->id)->count())->toBe(2);
});

test("the owner's derived total from those contributions is unchanged", function () {
    $sara = signInAsOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 120_000]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 80_000]);
    // Somebody else's money, so a total that ignored whose wish it was would
    // read 350,000 here and the figures below could not tell the two apart.
    Contribution::factory()->paid()->create(['amount' => 150_000]);

    $before = totalRaisedBy($sara);

    $this->delete(route('wishes.destroy', $wish));

    expect($before)->toBe(200_000)
        ->and(totalRaisedBy($sara))->toBe(200_000);
});

test('a deleted wish is gone from the profile grid and its count', function () {
    $sara = signInAsOwner();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Wish::factory()->create(['user_id' => $sara->id]);

    $this->delete(route('wishes.destroy', $wish));

    $this->get(route('profile', 'sara'))->assertInertia(
        fn ($page) => $page->has('wishes.data', 1)->where('wishes.meta.total', 1)
    );
});

test('a deleted wish is absent from an ordinary query', function () {
    $wish = Wish::factory()->create();

    $wish->delete();

    expect(Wish::count())->toBe(0)
        ->and(Wish::withTrashed()->count())->toBe(1);
});

/*
| Refused on the server, not merely hidden in the UI. A visitor who forges the
| request gets the same answer as one who never saw a button.
*/
test('a signed-in visitor may not delete a wish that is not theirs', function () {
    $wish = Wish::factory()->create();
    $this->actingAs(User::factory()->create(['username' => 'reza']));

    $this->delete(route('wishes.destroy', $wish))->assertForbidden();

    expect($wish->fresh()->trashed())->toBeFalse();
});

test('a guest may not delete a wish, and is sent to sign in', function () {
    $wish = Wish::factory()->create();

    $this->delete(route('wishes.destroy', $wish))->assertRedirect(route('login'));

    expect($wish->fresh()->trashed())->toBeFalse();
});

test('a contribution can still reach the wish it was made towards once that wish is deleted', function () {
    $wish = Wish::factory()->create(['title' => 'a bicycle']);
    $contribution = Contribution::factory()->paid()->create(['wish_id' => $wish->id]);

    $wish->delete();

    $contribution->refresh();

    expect($contribution->wish)->not->toBeNull()
        ->and($contribution->wish->title)->toBe('a bicycle')
        ->and($contribution->wish->trashed())->toBeTrue();
});

/*
| The foreign key was never softened — it is simply out of reach now. Force
| deleting remains the deliberate full-purge path ADR-0003 describes, and nothing
| in the app calls it.
*/
test('force deleting a wish still takes its contributions with it', function () {
    $wish = Wish::factory()->create();
    Contribution::factory()->paid()->create(['wish_id' => $wish->id]);

    $wish->forceDelete();

    expect(Contribution::where('wish_id', $wish->id)->count())->toBe(0);
});

/**
 * Sign in as the owner whose profile these cases act on.
 *
 * Named apart from `WishTest`'s `signIn()`: Pest loads every test file into one
 * process, so a repeated global helper would be a redeclaration fatal rather
 * than a shadow.
 */
function signInAsOwner(): User
{
    $sara = User::factory()->create(['username' => 'sara']);

    test()->actingAs($sara);

    return $sara;
}

/**
 * What an owner has earned, derived the only way ADR-0005 allows — by summing
 * the paid contributions themselves.
 *
 * The wishes are read `withTrashed()`, which is the whole point: money reached
 * through the ordinary relation would drop a deleted wish's contributions.
 */
function totalRaisedBy(User $owner): int
{
    return (int) Contribution::query()
        ->paid()
        ->whereIn('wish_id', Wish::withTrashed()->where('user_id', $owner->id)->select('id'))
        ->sum('amount');
}
