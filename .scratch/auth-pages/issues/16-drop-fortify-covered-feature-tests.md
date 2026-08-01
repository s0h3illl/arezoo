# 16 — Drop the Fortify-covered feature tests

**What to build:** The auth suite stops re-testing Fortify. Every feature test under `tests/Feature/Auth/` covers behaviour that Laravel Fortify — a first-party package with its own full suite — already owns: login, registration, password reset, and email verification. Those files go. The five browser smoke tests stay, because the Vue pages they exercise are ours and nothing upstream covers them.

This lands first on purpose. It is the prefactor for tickets 17–19: with these tests gone, each of those becomes a straight deletion instead of a delete-plus-rewrite-the-tests exercise.

**Blocked by:** None — can start immediately.

**Known and accepted gap:** nothing will cover the Persian notification emails or the RTL mail layout — browser smoke tests cannot see email. The user has weighed this and accepted it; do not reintroduce feature tests to close it.

**Status:** done

- [x] All feature tests under `tests/Feature/Auth/` are removed
- [x] All five browser smoke tests still pass and remain the whole auth suite
- [x] No test helper, dataset, or Pest hook left orphaned by the removals
- [x] The full suite passes, and Pint / Larastan / ESLint / Prettier / vue-tsc stay clean

## Comments

- Verified: `tests/Feature/Auth/` no longer exists, no other test file outside `tests/Browser` references Auth, and the full suite passes.
- Larastan, ESLint, Prettier, and vue-tsc are clean. Pint flags one pre-existing issue in `bootstrap/providers.php`, committed before this ticket and untouched by it — out of scope here.
