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

test('registration rejects an email that is already taken', function () {
    $existing = User::factory()->create();

    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => $existing->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'این ایمیل قبلاً ثبت شده است.']);
});

test('registration rejects a malformed email', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'ایمیل باید یک نشانی ایمیل معتبر باشد.']);
});

test('registration rejects a password shorter than eight characters', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'arezoo@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('password');
    expect(session('errors')->first('password'))->toContain('رمز عبور');
});

test('registration rejects a confirmation that does not match', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'arezoo@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['password' => 'رمز عبور با تکرارش یکی نیست.']);
});

test('authenticated users are redirected away from the registration screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('register'));

    $response->assertRedirect('/');
});
