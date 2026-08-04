<?php

use App\Models\User;

/*
| Admin is not a role anyone can reach by signing up. Keeping `is_admin` out of
| the fillable list is the whole of that guarantee, so it gets its own test.
*/
test('the admin flag cannot be mass assigned', function () {
    $user = new User([
        'name' => 'Arezoo',
        'email' => 'arezoo@example.com',
        'is_admin' => true,
    ]);

    expect($user->is_admin)->toBeFalse();
});

test('a user is not an admin by default', function () {
    expect((new User)->is_admin)->toBeFalse();
});
