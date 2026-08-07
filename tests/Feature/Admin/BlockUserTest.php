<?php

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia;

test('an admin blocks a user from the list', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['email' => 'sara@example.com']);

    $response = $this->from(route('admin.users.index'))->patch(
        route('admin.users.update', $user),
        ['action' => 'block', 'is_blocked' => true],
    );

    $response->assertRedirect(route('admin.users.index'));

    $this->get(route('admin.users.index', ['search' => 'sara@example.com']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('users.data.0.is_blocked', true)
    );
});

/*
| The badge posts the state it wants, not "the other one" — a request that says
| only "block something" leaves the outcome to whatever the row happened to show
| when the page was rendered.
*/
test('a block request has to say which way it is going', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    $this->patch(route('admin.users.update', $user), ['action' => 'block'])
        ->assertInvalid(['is_blocked']);

    expect($user->fresh()->is_blocked)->toBeFalse();
});

/*
| The same request class serves the edit form, where the block state is not what
| is being edited. An edit that never mentions blocking must not quietly undo it.
*/
test('an update that is not a block leaves the block state alone', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->blocked()->create();

    $this->patch(route('admin.users.update', $user), [
        'name' => 'سارا احمدی',
        'email' => 'sara@example.com',
    ])->assertRedirect();

    expect($user->fresh())
        ->name->toBe('سارا احمدی')
        ->email->toBe('sara@example.com')
        ->is_blocked->toBeTrue()
        // A changed email is an unproven email, the same as it is when the user
        // changes it themselves — an admin's edit cannot verify it for them.
        ->email_verified_at->toBeNull();
});

/*
| No admin can be blocked through the panel by any means, a crafted request from
| someone who knows the identifier included — see ADR-0008.
*/
test('an admin cannot be blocked', function () {
    $this->actingAs(User::factory()->admin()->create());
    $otherAdmin = User::factory()->admin()->create();

    $this->patch(route('admin.users.update', $otherAdmin), [
        'action' => 'block',
        'is_blocked' => true,
    ])->assertNotFound();

    expect($otherAdmin->fresh()->is_blocked)->toBeFalse();
});

test('an admin who is blocked cannot be unblocked either', function () {
    $this->actingAs(User::factory()->admin()->create());
    $otherAdmin = User::factory()->admin()->blocked()->create();

    $this->patch(route('admin.users.update', $otherAdmin), [
        'action' => 'block',
        'is_blocked' => false,
    ])->assertNotFound();

    expect($otherAdmin->fresh()->is_blocked)->toBeTrue();
});

test("an admin's name and email cannot be edited", function () {
    $this->actingAs(User::factory()->admin()->create());
    $otherAdmin = User::factory()->admin()->create([
        'name' => 'ناظر',
        'email' => 'nazer@example.com',
    ]);

    $this->patch(route('admin.users.update', $otherAdmin), [
        'name' => 'سارا احمدی',
        'email' => 'sara@example.com',
    ])->assertNotFound();

    expect($otherAdmin->fresh())
        ->name->toBe('ناظر')
        ->email->toBe('nazer@example.com');
});

/*
| Blocking is the one destructive thing the panel can do to a person, so the gate
| in front of it gets the same 404 treatment as the section it lives in.
*/
test('a signed-in user who is not an admin cannot block anyone', function () {
    $this->actingAs(User::factory()->create());
    $victim = User::factory()->create();

    $this->patch(route('admin.users.update', $victim), [
        'action' => 'block',
        'is_blocked' => true,
    ])->assertNotFound();

    expect($victim->fresh()->is_blocked)->toBeFalse();
});

test('a guest cannot block anyone', function () {
    $victim = User::factory()->create();

    $this->patch(route('admin.users.update', $victim), [
        'action' => 'block',
        'is_blocked' => true,
    ])->assertNotFound();

    expect($victim->fresh()->is_blocked)->toBeFalse();
});

/*
| An admin who blocks someone now expects them out now, not whenever their
| session happens to expire. `actingAs` stands in for the session the user was
| already holding when the block landed.
*/
test('blocking stops an existing session from working', function () {
    $user = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->patch(
        route('admin.users.update', $user),
        ['action' => 'block', 'is_blocked' => true],
    );

    $this->actingAs($user->fresh())
        ->get(route('home'))
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors(['email' => __('auth.blocked')]);

    $this->assertGuest();
});

/*
| Blocking bars someone from the app; it is not a confiscation. Their own wish's
| received total, the contributions they made to other people's wishes, and the
| totals those wishes show are all the same figures the moment after as before.
*/
test('blocking leaves every Toman where it was', function () {
    $user = User::factory()->create();
    $theirWish = Wish::factory()->create(['user_id' => $user->id]);
    Contribution::factory()->paid()->create([
        'wish_id' => $theirWish->id,
        'amount' => 50_000,
    ]);

    $someoneElsesWish = Wish::factory()->create();
    $whatTheyGave = Contribution::factory()->paid()->create([
        'wish_id' => $someoneElsesWish->id,
        'contributor_id' => $user->id,
        'amount' => 30_000,
    ]);

    $this->actingAs(User::factory()->admin()->create())->patch(
        route('admin.users.update', $user),
        ['action' => 'block', 'is_blocked' => true],
    );

    expect($theirWish->fresh()->receivedTotal())->toBe(50_000)
        ->and($someoneElsesWish->fresh()->receivedTotal())->toBe(30_000)
        ->and($whatTheyGave->fresh()->amount)->toBe(30_000)
        ->and($whatTheyGave->fresh()->status)->toBe(ContributionStatus::Paid);
});

test('an admin unblocks a user, and that user can sign in again', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->blocked()->create(['email' => 'sara@example.com']);

    $this->patch(route('admin.users.update', $user), [
        'action' => 'block',
        'is_blocked' => false,
    ])->assertRedirect();

    $this->get(route('admin.users.index', ['search' => 'sara@example.com']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('users.data.0.is_blocked', false)
    );

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'sara@example.com',
        'password' => 'password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($user);
});
