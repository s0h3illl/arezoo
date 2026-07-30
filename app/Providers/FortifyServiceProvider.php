<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn (): Response => Inertia::render('auth/Login'));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response($this->throttledLoginResponse(...));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }

    /**
     * Reject a rate-limited login with the Persian throttle message.
     *
     * The route-level throttle middleware trips before Fortify's login pipeline, and its
     * default `Too Many Attempts.` is a hardcoded English literal no lang file can reach.
     * Answering with the same field error Fortify's own lockout raises keeps the message
     * Persian and puts it inline on the login form, while the middleware's rate-limit
     * headers still reach the client.
     *
     * @param  array<string, mixed>  $headers
     */
    protected function throttledLoginResponse(Request $request, array $headers): HttpResponse
    {
        $seconds = (int) $headers['Retry-After'];

        $message = trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => (int) ceil($seconds / 60),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [Fortify::username() => [$message]],
            ], HttpResponse::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return back()
            ->withInput($request->only(Fortify::username()))
            ->withErrors([Fortify::username() => $message])
            ->withHeaders($headers);
    }
}
