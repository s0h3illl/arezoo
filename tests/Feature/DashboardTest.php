<?php

use App\Models\User;

test('a guest is sent from the dashboard to sign in', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a signed-in user reaches the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('an unverified user reaches the dashboard, because it is where a mistyped address is corrected', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
