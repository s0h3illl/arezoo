<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('an admin opens a user and sees who they are', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->unverified()->blocked()->create([
        'name' => 'سارا احمدی',
        'email' => 'sara@example.com',
    ]);

    $response = $this->get(route('admin.users.show', $user));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/users/Show')
            ->where('user.name', 'سارا احمدی')
            ->where('user.email', 'sara@example.com')
            ->where('user.email_verified_at', null)
            ->where('user.is_blocked', true)
            ->whereNot('user.created_at', null)
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give.
*/
test('a signed-in user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.users.show', User::factory()->create()))->assertNotFound();
});

test('a guest gets a 404', function () {
    $this->get(route('admin.users.show', User::factory()->create()))->assertNotFound();
});

test('a user who does not exist is a 404', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.users.show', 9999))->assertNotFound();
});
