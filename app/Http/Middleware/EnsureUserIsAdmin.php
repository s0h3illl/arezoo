<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Hide the admin section from everyone who isn't an admin.
     *
     * A 403 would tell the visitor the section is there and they are merely
     * shut out of it. A 404 says nothing at all, which is the point.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_admin) {
            abort(404);
        }

        return $next($request);
    }
}
