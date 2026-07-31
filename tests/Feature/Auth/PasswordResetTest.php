<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Request a reset link for the user and capture the token the emailed link carries.
 */
function emailedResetToken(User $user): string
{
    test()->post(route('password.email'), ['email' => $user->email]);

    $token = '';

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    return $token;
}

test('forgot password screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('auth/ForgotPassword')
    );
});

test('a known email receives the reset link with a Persian status flash', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'لینک بازیابی رمز عبور را برای شما ایمیل کردیم.');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('an unknown email gets an honest Persian validation error', function () {
    Notification::fake();

    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => 'nobody@example.com',
    ]);

    $response->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => 'کاربری با این ایمیل پیدا نکردیم.']);

    Notification::assertNothingSent();
});

test('the reset email is Persian inside an RTL mail layout', function () {
    $user = User::factory()->create();

    $mail = (new ResetPassword('fake-token'))->toMail($user);

    expect($mail->subject)->toBe('بازیابی رمز عبور');

    $html = $mail->render()->toHtml();

    expect($html)
        ->toContain('dir="rtl"')
        ->toContain('این ایمیل را دریافت کرده‌اید چون درخواست بازیابی رمز عبور برای حساب شما ثبت شده است.');
});

test('the status flash is shared with the forgot password page', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    $response = $this->get(route('password.request'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/ForgotPassword')
            ->where('status', 'لینک بازیابی رمز عبور را برای شما ایمیل کردیم.')
    );
});

test('the reset screen renders from the emailed link with the email prefilled', function () {
    Notification::fake();

    $user = User::factory()->create();
    $token = emailedResetToken($user);

    $response = $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/ResetPassword')
            ->where('email', $user->email)
            ->where('token', $token)
    );
});

test('a valid token resets the password and lands on login with a Persian flash', function () {
    Notification::fake();

    $user = User::factory()->create();
    $token = emailedResetToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', 'رمز عبور شما تغییر کرد.');

    $this->assertGuest();
    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();

    $this->get(route('login'))->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('status', 'رمز عبور شما تغییر کرد.')
    );
});

test('an invalid token shows a clear Persian error', function () {
    $user = User::factory()->create();

    $response = $this->from(route('password.reset', ['token' => 'tampered-token']))->post(route('password.update'), [
        'token' => 'tampered-token',
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertSessionHasErrors(['email' => 'این لینک بازیابی رمز عبور معتبر نیست.']);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('an expired token shows a clear Persian error', function () {
    Notification::fake();

    $user = User::factory()->create();
    $token = emailedResetToken($user);

    $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertSessionHasErrors(['email' => 'این لینک بازیابی رمز عبور معتبر نیست.']);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
