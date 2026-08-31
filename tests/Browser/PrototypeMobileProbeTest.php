<?php

/**
 * PROTOTYPE PROBE — throwaway. Mobile width, ticket 06.
 */

use App\Models\User;

function openMenuOnMobile(string $variant): void
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    test()->actingAs($sara);

    visit('/?variant='.$variant.'&unread=3')
        ->on()->mobile()
        ->click('[aria-label="حساب کاربری"]')
        ->assertSee('پیام‌ها')
        ->screenshot()
        ->assertNoJavaScriptErrors();
}

test('mobile variant A', fn () => openMenuOnMobile('A'));
test('mobile variant B', fn () => openMenuOnMobile('B'));
test('mobile variant C', fn () => openMenuOnMobile('C'));
