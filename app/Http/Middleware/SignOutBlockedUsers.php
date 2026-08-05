<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class SignOutBlockedUsers
{
    /**
     * Drop the session of a user who has been blocked since they signed in.
     *
     * Blocking has to take effect at the moment the admin decides it, not
     * whenever a session happens to expire — and this catches a session that
     * was already open, which the check at the login form cannot reach.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->is_blocked) {
            Auth::guard(config('fortify.guard'))->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                Fortify::username() => __('auth.blocked'),
            ]);
        }

        return $next($request);
    }
}
