<?php

use App\Models\User;

test('verify email page renders without javascript errors', function () {
    $this->actingAs(User::factory()->unverified()->create(['email' => 'arezoo@example.com']));

    $page = visit(route('verification.notice', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('ایمیلت رو تأیید کن')
        ->assertSee('arezoo@example.com');
});
