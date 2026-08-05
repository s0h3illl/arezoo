<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    /**
     * Turn a blocked user away, once their credentials have been accepted.
     *
     * The check runs after authentication rather than before it, so that a
     * stranger typing someone else's email cannot learn the account exists and
     * is blocked — a wrong password still gets the wrong-password answer.
     *
     * @param  Closure(Request): Response  $next
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        $guard = Auth::guard(config('fortify.guard'));
        $user = $guard->user();

        if ($user instanceof User && $user->is_blocked) {
            $guard->logout();

            throw ValidationException::withMessages([
                Fortify::username() => __('auth.blocked'),
            ]);
        }

        return $next($request);
    }
}
