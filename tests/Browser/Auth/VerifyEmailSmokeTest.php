<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

test('verify email page renders without javascript errors', function () {
    $this->actingAs(User::factory()->unverified()->create(['email' => 'arezoo@example.com']));

    $page = visit(route('verification.notice', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('ایمیلت رو تأیید کن')
        ->assertSee('arezoo@example.com');
});

test('the notice renders the throttle error from a throttled emailed link', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    // The limiter is keyed by user id, so spending it here throttles the browser too.
    foreach (range(1, 6) as $ignored) {
        $this->post(route('verification.send'));
    }

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
    ], absolute: false);

    // Following the link is a plain GET, so nothing populates the form's own error
    // bag — only the page props carry the throttle message. Asserting the element
    // rather than its wording keeps this about that binding.
    $page = visit($verificationUrl);

    $page->assertNoJavaScriptErrors()
        ->assertVisible('#verification-error');
});
