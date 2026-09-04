<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('a form that leaves the bio out does not wipe it', function () {
    $sara = User::factory()->create(['username' => 'sara', 'bio' => 'about me']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => 'sara']))
        ->assertSessionHasNoErrors();

    expect($sara->fresh()->bio)->toBe('about me');
});

test('an empty bio clears the one on the row', function () {
    $sara = User::factory()->create(['username' => 'sara', 'bio' => 'about me']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account(['username' => 'sara', 'bio' => '']))
        ->assertSessionHasNoErrors();

    expect($sara->fresh()->bio)->toBeNull();
});

test('an avatar is stored on the public disk and the column holds its path', function () {
    Storage::fake('public');
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $sara->email,
            'avatar' => makeAvatarFile(),
        ]))
        ->assertSessionHasNoErrors();

    $stored = $sara->fresh()->avatar;

    expect($stored)->toStartWith('avatars/');
    Storage::disk('public')->assertExists((string) $stored);
});

test('a new avatar replaces the old one and takes it off the disk', function () {
    Storage::fake('public');
    $sara = userWithAnAvatar();
    $old = $sara->avatar;

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $sara->email,
            'avatar' => makeAvatarFile(),
        ]))
        ->assertSessionHasNoErrors();

    $fresh = $sara->fresh();

    expect($fresh->avatar)->not->toBe($old);
    Storage::disk('public')->assertExists((string) $fresh->avatar);
    Storage::disk('public')->assertMissing((string) $old);
});

test('a save that says nothing about the avatar leaves it alone', function () {
    Storage::fake('public');
    $sara = userWithAnAvatar();
    $old = $sara->avatar;

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $sara->email,
        ]))
        ->assertSessionHasNoErrors();

    expect($sara->fresh()->avatar)->toBe($old);
    Storage::disk('public')->assertExists((string) $old);
});

test('an avatar that is not an image is refused', function () {
    Storage::fake('public');
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $sara->email,
            'avatar' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('avatar', errorBag: 'updateProfileInformation');

    expect($sara->fresh()->avatar)->toBeNull();
});

test('an avatar over the size ceiling is refused', function () {
    Storage::fake('public');
    $sara = User::factory()->create(['username' => 'sara']);

    $this->actingAs($sara)
        ->put(route('user-profile-information.update'), account([
            'username' => 'sara',
            'email' => $sara->email,
            'avatar' => UploadedFile::fake()->create('huge.jpg', 3_000, 'image/jpeg'),
        ]))
        ->assertSessionHasErrors('avatar', errorBag: 'updateProfileInformation');

    expect($sara->fresh()->avatar)->toBeNull();
});

function userWithAnAvatar(): User
{
    Storage::disk('public')->put('avatars/old.jpg', 'not really a picture');

    return User::factory()->create([
        'username' => 'sara',
        'avatar' => 'avatars/old.jpg',
    ]);
}

function makeAvatarFile(): UploadedFile
{
    return new UploadedFile(
        base_path('tests/fixtures/wish-cover.jpg'), 'avatar.jpg', 'image/jpeg', test: true
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function account(array $overrides = []): array
{
    return array_merge([
        'name' => 'سارا احمدی',
        'username' => 'sara',
        'email' => 'sara@example.com',
    ], $overrides);
}
