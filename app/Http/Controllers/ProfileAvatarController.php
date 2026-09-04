<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProfileAvatarController extends Controller
{
    public function destroy(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);

            $user->forceFill(['avatar' => null])->save();
        }

        return to_route('dashboard');
    }
}
