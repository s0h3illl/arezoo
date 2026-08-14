<?php

use App\Models\User;
use App\Models\Wish;
use Pest\Browser\Api\PendingAwaitablePage;

test('an owner adds a wish with only a title and a price', function () {
    visitOwnProfile()
        ->click('آرزوی جدید')
        ->fill('title', 'دوچرخه‌ی کوهستان')
        ->fill('price', '3200000')
        ->click('اضافه کن')
        ->assertSee('دوچرخه‌ی کوهستان');

    // Read as whose the row is, rather than as an id matching another id.
    expect(Wish::sole())
        ->title->toBe('دوچرخه‌ی کوهستان')
        ->price->toBe(3_200_000)
        ->description->toBeNull()
        ->purchase_link->toBeNull()
        ->thumbnail->toBeNull()
        ->owner->username->toBe('sara');
});

test('everything else the dialog offers reaches the row too', function () {
    visitOwnProfile()
        ->click('آرزوی جدید')
        ->fill('title', 'دوچرخه‌ی کوهستان')
        ->fill('description', 'برای رفتن به کوه')
        ->fill('purchase_link', 'https://example.com/bike')
        ->fill('price', '3200000')
        ->click('اضافه کن')
        ->assertSee('دوچرخه‌ی کوهستان');

    expect(Wish::sole())
        ->title->toBe('دوچرخه‌ی کوهستان')
        ->description->toBe('برای رفتن به کوه')
        ->purchase_link->toBe('https://example.com/bike')
        ->price->toBe(3_200_000)
        ->thumbnail->toBeNull();
});

test('a wish with nothing in it is refused', function () {
    visitOwnProfile()
        ->click('آرزوی جدید')
        ->click('اضافه کن')
        ->assertAriaAttribute('title', 'invalid', 'true')
        ->assertAriaAttribute('price', 'invalid', 'true');

    expect(Wish::count())->toBe(0);
});

/**
 * Sign in as the owner of a profile and open it.
 *
 * Named apart from `WishTest`'s `signInAsOwner()` deliberately: Pest loads every
 * test file into one process, so a second global helper under that name would be
 * a redeclaration fatal rather than a shadow.
 */
function visitOwnProfile(): PendingAwaitablePage
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);
    test()->actingAs($sara);

    return visit(route('profile', 'sara', absolute: false));
}
