<?php

use App\Models\User;

test('registering picks a username alongside the other fields', function () {
    $this->post(route('register.store'), registration(['username' => 'sara']))
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'sara@example.com')->value('username'))->toBe('sara');
});

test('a username is stored in lower case, whatever case it was typed in', function () {
    $this->post(route('register.store'), registration(['username' => 'Sara-Ahmadi']))
        ->assertSessionHasNoErrors();

    expect(User::value('username'))->toBe('sara-ahmadi');
});

test('a username is required, slug-safe, and bounded in length', function (string $username) {
    $this->post(route('register.store'), registration(['username' => $username]))
        ->assertSessionHasErrors('username');

    expect(User::count())->toBe(0);
})->with([
    'missing' => '',
    'a space' => 'sara ahmadi',
    'a dot' => 'sara.ahmadi',
    'Persian letters' => 'سارا',
    'too short' => 'sa',
    'too long' => 'sara-ahmadi-and-thirty-one-chars',
]);

test('a username is unique in any case, lower or capital', function (string $username) {
    User::factory()->create(['username' => 'sara']);

    $this->post(route('register.store'), registration(['username' => $username]))
        ->assertSessionHasErrors('username');
})->with(['sara', 'Sara', 'SARA']);

/*
| A username that shadows a path the app routes would be a profile nobody can
| reach and a page the owner cannot open.
*/
test('reserved keywords cannot be chosen as usernames', function (string $username) {
    $this->post(route('register.store'), registration(['username' => $username]))
        ->assertSessionHasErrors(['username' => __('validation.custom.username.not_in')]);

    expect(User::count())->toBe(0);
})->with(['admin', 'Admin', 'TERMS']);

test('the username field is refused in Persian', function () {
    $this->post(route('register.store'), registration(['username' => '']))
        ->assertSessionHasErrors(['username' => __('validation.required', [
            'attribute' => __('validation.attributes.username'),
        ])]);
});

/**
 * A complete registration, with whatever the test is examining swapped in.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function registration(array $overrides = []): array
{
    return array_merge([
        'name' => 'سارا احمدی',
        'username' => 'sara-ahmadi',
        'email' => 'sara@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}
