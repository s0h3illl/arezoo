<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('an admin reaches the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('admin/Dashboard')
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give, so the panel leaves
| no trace for anyone who can't use it.
*/
test('a signed-in user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.dashboard'))->assertNotFound();
});

test('a signed-out visitor gets a 404', function () {
    $this->get(route('admin.dashboard'))->assertNotFound();
});
