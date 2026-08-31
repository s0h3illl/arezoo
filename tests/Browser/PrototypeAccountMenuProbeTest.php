<?php

/**
 * PROTOTYPE PROBE — throwaway.
 * Ticket .scratch/dashboard/issues/06-the-account-menu-in-the-header.md
 */

use App\Models\User;

function openMenu(string $variant, string $page = 'profile'): void
{
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    test()->actingAs($sara);

    $url = $page === 'home'
        ? '/?variant='.$variant.'&unread=3'
        : route('profile', 'sara', absolute: false).'?variant='.$variant.'&unread=3';

    visit($url)
        ->click('[aria-label="حساب کاربری"]')
        ->assertSee('پیام‌ها')
        ->screenshot()
        ->assertNoJavaScriptErrors();
}

test('variant A on the profile', fn () => openMenu('A'));
test('variant B on the profile', fn () => openMenu('B'));
test('variant C on the profile', fn () => openMenu('C'));
test('variant A on the landing page, beside its section anchors', fn () => openMenu('A', 'home'));
test('variant B on the landing page, beside its section anchors', fn () => openMenu('B', 'home'));
test('variant C on the landing page, beside its section anchors', fn () => openMenu('C', 'home'));
