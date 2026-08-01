<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/** What the app's `verification` limiter allows the resend button and the emailed link, together. */
const VERIFICATION_REQUESTS_PER_MINUTE = 6;

/**
 * Resend the verification email for the user and capture the link it carries.
 */
function emailedVerificationUrl(User $user): string
{
    test()->actingAs($user)->post(route('verification.send'));

    $url = '';

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$url): bool {
        $url = $notification->toMail($user)->actionUrl;

        return true;
    });

    return $url;
}

/**
 * Spend the verification limiter so the caller's next request is throttled.
 *
 * Sends a full minute's worth of resends rather than counting what the caller
 * already spent: the limiter is keyed per user, so over-spending leaves it
 * exhausted either way, while under-spending would let the next request through.
 */
function exhaustTheVerificationLimiter(User $user): void
{
    foreach (range(1, VERIFICATION_REQUESTS_PER_MINUTE) as $ignored) {
        test()->actingAs($user)->post(route('verification.send'));
    }
}

test('the verification notice shows the address the link went to', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/VerifyEmail')
            ->where('email', $user->email)
    );
});

test('an already verified user is redirected into the app', function () {
    $user = User::factory()->verified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertRedirect(route('home'));
});

test('guests are sent to login from the verification notice', function () {
    $response = $this->get(route('verification.notice'));

    $response->assertRedirect(route('login'));
});

test('registering emails the verification link and lands on the notice', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'آرزو عباسی',
        'email' => 'arezoo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'arezoo@example.com')->sole();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the verification email is Persian inside an RTL mail layout', function () {
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('تأیید نشانی ایمیل');

    $html = $mail->render()->toHtml();

    expect($html)
        ->toContain('dir="rtl"')
        ->toContain('برای تأیید نشانی ایمیلتان، روی دکمه‌ی زیر کلیک کنید.');
});

test('resending emails a fresh link with Persian feedback', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'));

    $response->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'لینک تأیید تازه‌ای برایتان ایمیل شد.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('resending stops at the throttle with Persian feedback', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    exhaustTheVerificationLimiter($user);

    $response = $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'));

    $response->assertRedirect(route('verification.notice'))->assertSessionHas('errors');

    Notification::assertSentTimes(VerifyEmail::class, VERIFICATION_REQUESTS_PER_MINUTE);
});

test('a throttled verification link lands on the notice with Persian feedback', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $url = emailedVerificationUrl($user);

    exhaustTheVerificationLimiter($user);

    // A mail client sends no referer, so the notice has to be reached explicitly.
    $response = $this->actingAs($user)->from('')->get($url);

    $response->assertRedirect(route('verification.notice'))->assertSessionHas('errors');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

/**
 * The notice reads its throttle error off the page props rather than a form slot,
 * because the emailed link trips the same limiter without any form submission.
 * These two guard the wiring the notice page actually renders from.
 */
test('the resend status reaches the notice page', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'));

    $this->actingAs($user)->get(route('verification.notice'))->assertInertia(
        fn (Assert $page) => $page->component('auth/VerifyEmail')->has('status')
    );
});

test('a throttled verification link puts its error on the notice page', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $url = emailedVerificationUrl($user);

    exhaustTheVerificationLimiter($user);

    $this->actingAs($user)->get($url);

    $this->actingAs($user)->get(route('verification.notice'))->assertInertia(
        fn (Assert $page) => $page->component('auth/VerifyEmail')->has('errors.verification')
    );
});

test('a verified user resending is redirected into the app', function () {
    Notification::fake();

    $user = User::factory()->verified()->create();

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertRedirect(route('home'));
    Notification::assertNothingSent();
});

test('the emailed link marks the user verified and lands in the app', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(emailedVerificationUrl($user));

    $response->assertRedirect('/?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('changing the email address clears verification and sends a fresh link', function () {
    Notification::fake();

    $user = User::factory()->verified()->create();

    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => $user->name,
        'email' => 'tazeh@example.com',
    ]);

    $updated = $user->fresh();

    expect($updated->email)->toBe('tazeh@example.com');
    expect($updated->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a tampered verification link is rejected', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(emailedVerificationUrl($user).'&tampered=1');

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
