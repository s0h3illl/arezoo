<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class UserPasswordController extends Controller
{
    /**
     * Set a new password for a user who has lost access to their email and
     * therefore cannot use the password-reset flow.
     *
     * `is_blocked` is untouched: setting a password is not a decision about
     * whether the user may sign in at all, and a blocked user stays blocked.
     */
    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return back();
    }
}
