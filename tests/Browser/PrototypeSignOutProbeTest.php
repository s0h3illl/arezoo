<?php

/**
 * PROTOTYPE PROBE — throwaway.
 * Ticket .scratch/dashboard/issues/06-the-account-menu-in-the-header.md
 *
 * Which of the three sign-out mechanisms survives being inside a reka-ui menu.
 * The B case is recorded as it behaves, not as it should: the menu closes on
 * select and unmounts the portal before the <Form> can submit.
 */

use App\Models\User;

function clickSignOut(string $variant, string $label): Pest\Browser\Api\AwaitableWebpage
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    test()->actingAs($sara);

    return visit(route('profile', 'sara', absolute: false).'?variant='.$variant)
        ->click('[aria-label="حساب کاربری"]')
        ->click($label);
}

test('A — <Link :href="logout()"> signs out', function () {
    clickSignOut('A', 'خروج')
        ->assertSee('ورود')
        ->assertNoJavaScriptErrors();
});

test('B — <Link :href="logout()"> signs out, after <Form> was found not to', function () {
    clickSignOut('B', 'خروج از حساب')
        ->assertSee('ورود')
        ->assertNoJavaScriptErrors();
});

test('C — router.post(logout.url()) signs out', function () {
    clickSignOut('C', 'خروج از حساب')
        ->assertSee('ورود')
        ->assertNoJavaScriptErrors();
});
