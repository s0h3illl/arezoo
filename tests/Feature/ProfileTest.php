<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

/*
| A profile is a page built to be sent to people, so the interesting cases are all
| about who the URL answers to. The page itself is the next ticket's business.
*/

test('a profile resolves by username', function () {
    User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    $response = $this->get(route('profile', 'sara'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Profile')
            ->where('user.name', 'سارا احمدی')
            ->where('user.username', 'sara')
    );
});

test('a guest reads a profile without being sent to sign in', function () {
    User::factory()->create(['username' => 'sara']);

    $this->assertGuest();
    $this->get(route('profile', 'sara'))->assertOk();
});

/*
| One username, however it is typed. Two people cannot hold names differing only
| in capitalisation, so a link that shouts still leads to the one profile.
*/
test('a username in the URL matches whatever its capitalisation', function () {
    User::factory()->create(['username' => 'sara']);

    $this->get(route('profile', 'SARA'))->assertOk();
});

test('a username nobody holds answers with the shared error page', function () {
    $response = $this->get(route('profile', 'nobody'));

    $response->assertNotFound()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Error')->where('status', 404)
    );
});

/*
| Blocking is a real removal from the app, not a cosmetic one — so the profile is
| unreachable by everyone, including its own owner.
*/
test('a blocked user has no profile', function () {
    User::factory()->blocked()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertNotFound();
});

/*
| ADR-0008 keeps admins out of the panel's user routes. It says nothing about the
| public site, where an admin reads and publishes wishes like anybody else.
*/
test("an admin's profile reads like anyone else's", function () {
    User::factory()->admin()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertOk();
});

/*
| The public URL shape must not leak into the panel. Its binding still takes an id
| and still leaves out admins, so a username there is simply an unknown user.
*/
test('the panel still resolves a user by primary key, and never by username', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['username' => 'sara']);

    $this->get(route('admin.users.show', $user->id))->assertOk();
    $this->get(route('admin.users.show', 'sara'))->assertNotFound();
});
