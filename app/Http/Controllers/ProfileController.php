<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show a user's public profile.
     *
     * The user arrives already resolved from the username in the URL, and a
     * username nobody holds — or one belonging to a blocked user — never gets
     * this far. See the `username` binding in `AppServiceProvider`.
     */
    public function __invoke(User $username): Response
    {
        return Inertia::render('Profile', [
            'user' => $username->only(['name', 'username', 'avatar', 'bio']),
        ]);
    }
}
