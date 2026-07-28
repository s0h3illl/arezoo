# 01 — Prefactor: relocate enums into `App\Enums`

**What to build:** A baseline where the migrations and the test suite actually run. Right now they don't: the three enums declare `namespace App`, while the models import them from `App\Enums`, and both the contributions and payments migrations — plus the payment factory — reference enum cases without importing them at all. Anyone running `php artisan migrate` today gets a fatal error.

This is a purely mechanical prefactor with no behaviour change. Do it first so every later ticket starts from green.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] The contribution status, contribution visibility, and payment status enums live under the `App\Enums` namespace, in the directory matching it
- [ ] Every reference to them resolves — models, migrations, and the payment factory each import what they use
- [ ] `php artisan migrate:fresh` completes without error
- [ ] The existing test suite passes, Pint is clean, and larastan reports no new errors
