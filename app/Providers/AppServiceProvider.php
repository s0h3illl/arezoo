<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
