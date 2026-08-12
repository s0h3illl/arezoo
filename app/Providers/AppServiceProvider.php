<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureErrorPage();
        $this->configureRouteBindings();
    }

    /**
     * Resolve the user route parameter to one of the platform's users.
     *
     * An admin resolves to nothing, so every route that takes a user answers
     * 404 for an admin without any of them having to remember to check. The
     * binding is global on purpose, and so is the loss of panel control over
     * staff accounts that comes with it — see ADR-0008.
     *
     * The `username` parameter is a second, separate binding, keyed on the
     * username column and used by the public profile. It keeps admins, who read
     * and publish wishes like anyone else — ADR-0008 governs the panel, not the
     * public site — and drops blocked users, so nothing of theirs is reachable
     * without every controller remembering to check.
     */
    protected function configureRouteBindings(): void
    {
        Route::bind('user', fn (string $value): User => User::query()
            ->excludingAdmins()
            ->whereKey($value)
            ->firstOrFail());

        Route::bind('username', fn (string $value): User => User::query()
            ->excludingBlocked()
            ->withUsername($value)
            ->firstOrFail());
    }

    /**
     * Answer HTTP errors with the shared Persian error page.
     *
     * Without this hook an aborted request falls back to Laravel's English stock
     * pages, and the throttle middleware's `Too Many Attempts.` is a hardcoded
     * literal no lang file can reach. Routing every status through one Inertia
     * page keeps the copy Persian and inside the app's own layout, and spares
     * every future limiter a bespoke response callback.
     */
    protected function configureErrorPage(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            $status = $response->statusCode();

            if (! in_array($status, [403, 404, 429, 500, 503], true)) {
                return null;
            }

            // A stack trace says more than a Persian apology when the fault is ours,
            // so server errors keep the debug page wherever debugging is switched on.
            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            // A 404 never reaches a route, so the `web` group never ran and the shared
            // props are missing until the Inertia middleware is resolved by hand.
            return $response->render('Error', ['status' => $status])->withSharedData();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
