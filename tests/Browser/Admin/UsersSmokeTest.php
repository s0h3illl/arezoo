<?php

use App\Models\User;

test('admin users list renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['name' => 'سارا احمدی', 'email' => 'sara@example.com']);
    User::factory()->blocked()->create(['name' => 'رضا کریمی']);

    $page = visit(route('admin.users.index', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('کاربران');
});

test('a search that matches nothing explains itself rather than showing a blank table', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['name' => 'سارا احمدی']);

    $page = visit(route('admin.users.index', ['search' => 'هیچ‌کس'], absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('کاربری با این مشخصات پیدا نشد')
        ->assertDontSee('سارا احمدی');
});
