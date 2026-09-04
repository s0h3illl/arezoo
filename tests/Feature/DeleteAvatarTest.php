<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/old.jpg', 'not really a picture');

    $this->sara = User::factory()->create([
        'username' => 'sara',
        'bio' => 'about me',
        'avatar' => 'avatars/old.jpg',
    ]);
});

test('deleting the avatar nulls the column and takes the file off the disk', function () {
    $this->actingAs($this->sara)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('dashboard'));

    expect($this->sara->fresh()->avatar)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/old.jpg');
});

test('deleting an avatar which does not exist, leaves the row alone', function () {
    $reza = User::factory()->create(['username' => 'reza']);

    $this->actingAs($reza)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('dashboard'));

    expect($reza->fresh()->avatar)->toBeNull();
});

test('deleting an avatar leaves the rest of the profile untouched', function () {
    $this->actingAs($this->sara)->delete(route('profile.avatar.destroy'));

    $fresh = $this->sara->fresh();

    expect($fresh->username)->toBe('sara')
        ->and($fresh->bio)->toBe('about me')
        ->and($fresh->email)->toBe($this->sara->email)
        ->and($fresh->email_verified_at)->not->toBeNull();
});

test('a guest cannot delete an avatar', function () {
    $this->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('login'));

    expect($this->sara->fresh()->avatar)->toBe('avatars/old.jpg');
    Storage::disk('public')->assertExists('avatars/old.jpg');
});
