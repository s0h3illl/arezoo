<?php

use App\Models\User;

/*
| A blocked user told "ایمیل یا رمز عبور درست نیست" spends the evening resetting
| a password that was never the problem. The refusal says what actually happened,
| in words the wrong-password path never uses.
*/
test('a blocked user is refused at sign-in, in words the wrong password does not use', function () {
    User::factory()->blocked()->create(['email' => 'sara@example.com']);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'sara@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors(['email' => __('auth.blocked')]);

    $this->assertGuest();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'sara@example.com',
            'password' => 'not-the-password',
        ])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    expect(__('auth.blocked'))->not->toBe(__('auth.failed'));
});

/*
| Someone guessing at emails must not be able to read the sign-in refusal as an
| account directory. A wrong password is a wrong password, blocked or not.
*/
test('a wrong password on a blocked account does not reveal that the account is blocked', function () {
    User::factory()->blocked()->create(['email' => 'sara@example.com']);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'sara@example.com',
            'password' => 'not-the-password',
        ])
        ->assertSessionHasErrors(['email' => __('auth.failed')])
        ->assertSessionDoesntHaveErrors(['email' => __('auth.blocked')]);
});
