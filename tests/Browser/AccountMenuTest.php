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

test('users can go to profile page from account menu', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click('@profile')
        ->assertPathIs('/u/mina');
});

test('users can go to dashboard from the account menu', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click('@account-menu-account')
        ->assertPathIs('/dashboard');
});

test('the account menu reaches the inbox', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click('@account-menu-messages')
        ->assertPathIs('/dashboard/messages');
});

it('shows teh user info', function () {
    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertSee('مینا')
        ->assertSee('@mina')
        ->assertNoJavaScriptErrors();
});

test('the account menu tells an unverified user why the inbox is shut', function () {
    $this->mina->forceFill(['email_verified_at' => null])->save();

    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->assertSee('ایمیلت هنوز تأیید نشده')
        ->assertAriaAttribute('@account-menu-messages', 'disabled', 'true')
        ->assertAttributeMissing('@account-menu-account', 'aria-disabled');
});

test('an unverified user still reaches the account screen from the menu', function () {
    $this->mina->forceFill(['email_verified_at' => null])->save();

    visit(route('home', absolute: false))
        ->click('@account-menu')
        ->click('@account-menu-account')
        ->assertPathIs('/dashboard');
});

test('menu is not shown for a guest', function () {
    $this->app['auth']->guard()->logout();

    visit(route('home', absolute: false))
        ->assertMissing('@account-menu')
        ->assertSee('ورود');
});
