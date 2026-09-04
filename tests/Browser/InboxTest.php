<?php

use App\Models\User;

test('owner can visit the inbox without any error', function () {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard.messages', absolute: false))
        ->assertNoJavaScriptErrors();
});

test('the inbox says so when there is nothing to read', function () {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard.messages', absolute: false))
        ->assertSee('پیام‌ها')
        ->assertPresent('@no-messages');
});
