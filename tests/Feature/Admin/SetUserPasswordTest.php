<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
| This is the way back in for a user locked out of their email, so the test
| that matters most is the plain path: an admin types a password, and the
| user is signing in with it a moment later.
*/
test('an admin sets a new password, and the user signs in with it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['email' => 'sara@example.com']);

    $this->put(route('admin.users.password.update', $user), [
        'password' => 'a-new-strong-password',
        'password_confirmation' => 'a-new-strong-password',
    ])->assertRedirect();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'sara@example.com',
        'password' => 'a-new-strong-password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($user);
});

/*
| An admin is not a weaker path into a user's account than registration or
| the reset-password form — the same `PasswordValidationRules` trait, so the
| same rule and the same Persian message reject it here too.
*/
test('a password that fails the app rules is rejected, in the same words as everywhere else', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();

    $response = $this->from(route('admin.users.show', $user))->put(
        route('admin.users.password.update', $user),
        ['password' => 'short', 'password_confirmation' => 'short'],
    );

    $response->assertRedirect(route('admin.users.show', $user));
    $response->assertInvalid([
        'password' => __('validation.min.string', ['attribute' => __('validation.attributes.password'), 'min' => 8]),
    ]);

    expect(Hash::check('short', $user->fresh()->password))->toBeFalse();
});

test("the user's old password stops working once an admin sets a new one", function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['email' => 'sara@example.com']);

    $this->put(route('admin.users.password.update', $user), [
        'password' => 'a-new-strong-password',
        'password_confirmation' => 'a-new-strong-password',
    ]);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'sara@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

/*
| Setting a password is a way back in for someone locked out of email, not a
| decision about whether they may use the app at all — those are separate
| calls an admin makes independently.
*/
test('setting a password on a blocked user does not unblock them', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->blocked()->create(['email' => 'sara@example.com']);

    $this->put(route('admin.users.password.update', $user), [
        'password' => 'a-new-strong-password',
        'password_confirmation' => 'a-new-strong-password',
    ]);

    expect($user->fresh()->is_blocked)->toBeTrue();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'sara@example.com',
        'password' => 'a-new-strong-password',
    ])->assertSessionHasErrors(['email' => __('auth.blocked')]);

    $this->assertGuest();
});

/*
| The gate on this action is the same 404 that fronts the rest of the admin
| section — a non-admin gets no hint that a password-setting endpoint exists.
*/
test('a signed-in user who is not an admin cannot set anyone\'s password', function () {
    $this->actingAs(User::factory()->create());
    $victim = User::factory()->create();

    $this->put(route('admin.users.password.update', $victim), [
        'password' => 'a-new-strong-password',
        'password_confirmation' => 'a-new-strong-password',
    ])->assertNotFound();

    expect(Hash::check('a-new-strong-password', $victim->fresh()->password))->toBeFalse();
});

test('a guest cannot set anyone\'s password', function () {
    $victim = User::factory()->create();

    $this->put(route('admin.users.password.update', $victim), [
        'password' => 'a-new-strong-password',
        'password_confirmation' => 'a-new-strong-password',
    ])->assertNotFound();

    expect(Hash::check('a-new-strong-password', $victim->fresh()->password))->toBeFalse();
});

/*
| A user who kept a tab open when the admin stepped in must not still be
| signed in with a password that no longer works — the change has to reach
| every session at once, not just the next time each one happens to expire.
*/
test("setting a new password signs the user's existing session out", function () {
    $user = User::factory()->create();

    // The session already holds the password hash it authenticated with,
    // exactly as a browser that signed in before the admin acted would.
    $this->withSession(['password_hash_web' => $user->getAuthPassword()])
        ->actingAs($user)
        ->get(route('home'))
        ->assertOk();

    $this->actingAs(User::factory()->admin()->create())->put(
        route('admin.users.password.update', $user),
        ['password' => 'a-new-strong-password', 'password_confirmation' => 'a-new-strong-password'],
    );

    $this->withSession(['password_hash_web' => $user->getAuthPassword()])
        ->actingAs($user->fresh())
        ->get(route('home'));

    $this->assertGuest();
});
