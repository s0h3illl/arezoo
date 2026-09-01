<?php

use App\Models\User;

test('saving the form without touching the username succeeds', function () {
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => 'sara']))
        ->assertSessionHasNoErrors();

    expect($sara->fresh()->username)->toBe('sara');
});

test('a username somebody else holds is refused', function (string $username) {
    User::factory()->create(['username' => 'reza']);
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => $username]))
        ->assertSessionHasErrors('username', errorBag: 'updateProfileInformation');

    expect($sara->fresh()->username)->toBe('sara');
})->with(['reza', 'Reza', 'REZA']);

test('a reserved keyword is refused as a new username', function (string $username) {
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => $username]))
        ->assertSessionHasErrors(
            ['username' => __('validation.custom.username.not_in')],
            errorBag: 'updateProfileInformation',
        );

    expect($sara->fresh()->username)->toBe('sara');
})->with(['admin', 'Admin', 'support', 'help', 'arezoo']);

test('a username is free the moment its holder gives it up', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $reza = User::factory()->create(['username' => 'reza']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara-ahmadi',
            'email' => $sara->email,
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($reza)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $reza->email,
        ]))
        ->assertSessionHasNoErrors();

    expect($reza->fresh()->username)->toBe('sara');
});

test('an unverified user may still save their account details', function () {
    $sara = User::factory()->unverified()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => 'sara-ahmadi']))
        ->assertSessionHasNoErrors();

    expect($sara->fresh()->username)->toBe('sara-ahmadi');
});

/**
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function account(array $overrides = []): array
{
    return array_merge([
        'name' => 'سارا احمدی',
        'username' => 'sara',
        'email' => 'sara@example.com',
    ], $overrides);
}
