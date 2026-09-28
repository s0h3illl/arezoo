<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SignOutBlockedUsers;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Compares each request's session against the user's current
            // password hash, so a password changed elsewhere (an admin
            // setting one included) signs every other session out at once.
            AuthenticateSession::class,
            SignOutBlockedUsers::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        /*
         * The application never sees the outside world directly. TLS terminates at
         * the reverse proxy in front of it, and php-fpm listens on a port nothing
         * else can reach, so the only way a request arrives is through that proxy.
         * Trusting it is what lets Laravel read the scheme and client address the
         * proxy reports.
         *
         * Without this, Laravel believes every request arrived over plain HTTP,
         * and that belief leaks into places a user can see: the verification and
         * password-reset links in outgoing email are generated from the current
         * scheme, so they would be emailed as http:// links — refused by clients,
         * and a downgrade even where they were not. Sessions would also be issued
         * without the Secure flag, and signed URLs would be validated against the
         * wrong host.
         *
         * Trusting every proxy is only safe because the port is unreachable from
         * outside. On a host where php-fpm is exposed, name the proxy instead.
         */
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
