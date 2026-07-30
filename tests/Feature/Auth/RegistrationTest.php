<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('auth/Register')
    );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'arezoo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/');

    expect(User::where('email', 'arezoo@example.com')->first())
        ->not->toBeNull()
        ->name->toBe('آرزو عباسی');
});

test('registration requires every field', function () {
    $response = $this->from(route('register'))->post(route('register.store'), []);

    $this->assertGuest();
    $response->assertRedirect(route('register'))->assertSessionHasErrors([
        'name' => 'وارد کردن نام الزامی است.',
        'email' => 'وارد کردن ایمیل الزامی است.',
        'password' => 'وارد کردن رمز عبور الزامی است.',
    ]);
});

test('registration rejects invalid input with a Persian error on the right field', function (array $input, string $field, string $message) {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'arezoo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$input,
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('register'))->assertSessionHasErrors([$field => $message]);
})->with([
    'malformed email' => [
        ['email' => 'not-an-email'],
        'email',
        'ایمیل باید یک نشانی ایمیل معتبر باشد.',
    ],
    'password shorter than eight characters' => [
        ['password' => 'short', 'password_confirmation' => 'short'],
        'password',
        'رمز عبور باید دست‌کم 8 کاراکتر باشد.',
    ],
    // Laravel's `confirmed` rule always reports on the password field, never on
    // the confirmation field the user actually mistyped.
    'confirmation that does not match' => [
        ['password_confirmation' => 'different-password'],
        'password',
        'رمز عبور با تکرارش یکی نیست.',
    ],
    'name longer than the column allows' => [
        ['name' => str_repeat('ا', 256)],
        'name',
        'نام نباید بیشتر از 255 کاراکتر باشد.',
    ],
]);

test('registration rejects an email that is already taken', function () {
    $existing = User::factory()->create();

    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => $existing->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors(['email' => 'این ایمیل قبلاً ثبت شده است.']);
});

test('registration matches an existing email case-insensitively', function () {
    User::factory()->create(['email' => 'arezoo@example.com']);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'AREZOO@Example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors(['email' => 'این ایمیل قبلاً ثبت شده است.']);
    expect(User::where('email', 'AREZOO@Example.com')->exists())->toBeFalse();
});

test('authenticated users are redirected away from the registration screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('register'));

    $response->assertRedirect('/');
});
