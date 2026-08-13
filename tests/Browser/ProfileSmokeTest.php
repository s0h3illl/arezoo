<?php

use App\Models\User;
use App\Models\Wish;

test('a profile renders its wishes without javascript errors', function () {
    $sara = User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);
    Wish::factory()->create(['user_id' => $sara->id]);

    $page = visit(route('profile', 'sara', absolute: false));

    $page->assertNoJavaScriptErrors();
});
