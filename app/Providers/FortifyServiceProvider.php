<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\EmailVerificationNotificationSentResponse;
use App\Http\Responses\RegisterResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(
            EmailVerificationNotificationSentResponseContract::class,
            EmailVerificationNotificationSentResponse::class
        );
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
        Fortify::registerView(fn (): Response => Inertia::render('auth/Register'));
        Fortify::requestPasswordResetLinkView(fn (): Response => Inertia::render('auth/ForgotPassword'));
        Fortify::resetPasswordView(fn (Request $request): Response => Inertia::render('auth/ResetPassword', [
            'email' => $request->input('email'),
            'token' => $request->route('token'),
        ]));
        Fortify::verifyEmailView(fn (Request $request): Response => Inertia::render('auth/VerifyEmail', [
            'email' => $request->user()->email,
        ]));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response($this->throttledLoginResponse(...));
        });

        // Fortify ships `throttle:6,1` here; this named limiter exists only to answer
        // in Persian, so it keeps the shipped rate and the shipped per-user key. Both
        // routes sit behind `auth`, so there is always a user to key by.
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(6)
                ->by($request->user()->getAuthIdentifier())
                ->response($this->throttledVerificationResponse(...));
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

    /**
     * Reject a rate-limited verification request with the Persian throttle message.
     *
     * Same trap as the login limiter: the throttle middleware's `Too Many Attempts.`
     * is a hardcoded English literal. The limiter guards the resend button and the
     * emailed link alike, and a click from a mail client carries no referer, so the
     * notice — not `back()` — is the one page that always renders the message.
     *
     * @param  array<string, mixed>  $headers
     */
    protected function throttledVerificationResponse(Request $request, array $headers): HttpResponse
    {
        $message = trans('verification.throttle', [
            'seconds' => (int) $headers['Retry-After'],
        ]);

        return redirect()->route('verification.notice')
            ->withErrors(['verification' => $message])
            ->withHeaders($headers);
    }
}
