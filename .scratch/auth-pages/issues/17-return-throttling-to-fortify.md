# 17 — Return throttling to Fortify behind a Persian error page

**What to build:** Rate limiting stops being hand-rolled. Both custom limiters and both custom throttle-response methods go, and Fortify's shipped throttling takes over — same limits, Persian messaging preserved by two different mechanisms.

**Login:** Fortify's login pipeline already falls back to its own throttle step whenever the `login` limiter is unset, and that step's lockout response raises the `auth.throttle` translation line — which our Persian lang file already provides. So nulling the limiter restores Persian for free. It also improves behaviour: the shipped path counts only *failed* attempts and clears them on success, where the middleware penalised every login including successful ones. The rate and the throttle key are unchanged.

**Verification:** there is no equivalent fallback here — unsetting the limiter reverts to plain throttle middleware, whose "Too Many Attempts." is a hardcoded English literal no lang file can reach. Persian comes instead from a central Inertia exception hook rendering a shared error page. This is deliberately broader than the one throttle it unblocks: the same page serves 403, 404, and 500 across the whole app, replacing what would otherwise be per-limiter response callbacks forever.

Also clears two limiter names in config that point at limiters which no longer exist (`two-factor`, removed when two-factor was dropped, and `passkeys`, removed by ticket 19) — harmless today, an instant error the day either feature is switched on.

**Blocked by:** 16 — Drop the Fortify-covered feature tests.

**Note:** the working tree may carry a stray edit reverting the verification limiter's throttle key. Ignore it; the whole limiter is deleted here.

**Status:** done

- [x] The `login` and `verification` limiters are unset in config and their `RateLimiter::for` registrations removed
- [x] Both custom throttle-response methods are gone from the Fortify service provider, along with any imports left unused
- [x] Login lockout still shows the Persian throttle message, sourced from the existing lang file rather than app code
- [x] A shared Inertia error page renders Persian copy for HTTP error statuses, wired through Inertia v3's exception-handling hook
- [x] Hitting the verification throttle lands on that Persian error page
- [x] Stale limiter names are removed from the Fortify config
- [x] Browser smoke tests pass, and Pint / Larastan / ESLint / Prettier / vue-tsc stay clean

## Comments

- `verification` is **removed** from the limiters array rather than nulled. Fortify reads it as `config('fortify.limiters.verification', '6,1')` and interpolates the result straight into `'throttle:'.$limiter` with no null guard, so a present-but-null key yields a broken `throttle:` middleware. Only an absent key reaches the shipped `6,1`. `login` is nulled, which is what Fortify checks before adding `EnsureLoginIsNotThrottled` back into its pipeline.
- **`passkeys` was left in the config against this ticket's prose.** Ticket 19 claims "the passkeys configuration block and its limiter name are removed", and 19 lands *after* 17. The feature is still enabled and `RateLimiter::for('passkeys')` still exists, so clearing the name here would leave live passkey routes unthrottled for the gap between the two tickets. Only `two-factor` — genuinely stale — was cleared. Ticket 19's checklist already covers the rest.
- The exception hook also covers **503**, and lets **5xx keep the debug page wherever `app.debug` is on** — a stack trace is worth more than a Persian apology when the fault is ours. Both are beyond the ticket's literal list; flagging rather than hiding them.
- `HandleInertiaRequests::share()` now guards `$request->session()`. A 404 never reaches a route, so the `web` group never ran and the store is unset — without the guard the hook would trade a 404 for a 500.
- **No rate-limit tests, by explicit user instruction mid-implementation** ("do not test for rate limit; only one smoke test is enough"). The previous `VerifyEmailSmokeTest` throttle case was deleted rather than repointed at the error page. `tests/Feature/ErrorPageTest.php` covers the hook via 404 / 403 / 500 / debug-mode / fall-through, and `tests/Browser/ErrorPageSmokeTest.php` is the one smoke test for the new page.
