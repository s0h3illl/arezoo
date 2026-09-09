<?php

use App\Models\User;

beforeEach(function () {
    $this->mina = User::factory()->create([
        'name' => 'مینا',
        'username' => 'mina',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->mina);
});

test('users can log out from menu', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click('@account-menu-sign-out')
        ->assertSee('ورود');

    visit(route('dashboard', absolute: false))->assertPathIs('/login');
});

test('every row in the account menu goes where it says', function (string $row, string $path) {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click($row)
        ->assertPathIs($path);
})->with([
    'my page' => ['@profile', '/u/mina'],
    'my details' => ['@account-menu-account', '/dashboard'],
    'messages' => ['@account-menu-messages', '/dashboard/messages'],
    'finance' => ['@account-menu-finance', '/dashboard/finance'],
]);

it('shows teh user info', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertSee('مینا')
        ->assertSee('@mina')
        ->assertNoJavaScriptErrors();
});

test('the account menu tells an unverified user why some of it is shut', function () {
    $this->mina->forceFill(['email_verified_at' => null])->save();

    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertSee('ایمیلت هنوز تأیید نشده');
});

test('a row behind email verification is shut to an unverified user', function (string $row) {
    $this->mina->forceFill(['email_verified_at' => null])->save();

    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertAriaAttribute($row, 'disabled', 'true');
})->with([
    'messages' => '@account-menu-messages',
    'finance' => '@account-menu-finance',
]);

test('a row that needs no verification stays open to an unverified user', function (string $row, string $path) {
    $this->mina->forceFill(['email_verified_at' => null])->save();

    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertAttributeMissing($row, 'aria-disabled')
        ->click($row)
        ->assertPathIs($path);
})->with([
    'my page' => ['@profile', '/u/mina'],
    'my details' => ['@account-menu-account', '/dashboard'],
]);

test('menu is not shown for a guest', function () {
    $this->app['auth']->guard()->logout();

    visit(route('home', absolute: false))
        ->assertMissing('@account-menu')
        ->assertSee('ورود');
});
