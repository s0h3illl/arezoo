<?php

use App\Models\User;

test('a user detail view renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create([
        'name' => 'سارا احمدی',
        'email' => 'sara@example.com',
    ]);

    $page = visit(route('admin.users.show', $user, absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('سارا احمدی')
        ->assertSee('sara@example.com')
        ->assertSee('تاریخ عضویت');
});

test('a blocked user reads as blocked, and the button offers to undo it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->blocked()->create(['name' => 'رضا کریمی']);

    $page = visit(route('admin.users.show', $user, absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('مسدود')
        ->assertSee('رفع مسدودی');
});

/*
| The way into this screen is a user's name in the list. A detail view nothing
| links to is a URL an admin has to already know.
*/
test('an admin reaches the detail view by clicking a user in the list', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['name' => 'سارا احمدی']);

    $page = visit(route('admin.users.index', ['search' => 'سارا'], absolute: false));

    $page->click('سارا احمدی')
        ->assertNoJavaScriptErrors()
        ->assertSee('تاریخ عضویت');
});
