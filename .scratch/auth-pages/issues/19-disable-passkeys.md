# 19 — Disable the passkeys feature

**What to build:** Passkeys come out of the enabled feature set. They arrived with the starter kit, were never designed for, and their management UI belongs to the Profile/settings effort — the auth-pages map lists them as out of scope. Leaving the feature on means routes, a limiter, and generated frontend route helpers for something nothing calls.

Turning the feature off also drops its configuration block and its rate-limiter name, and regenerates the typed route helpers so the passkey files stop being emitted.

**Verified safe:** the password-confirmation routes are registered independently of the passkeys feature — they are gated only by whether view routes are enabled. Disabling passkeys does not remove them, so ticket 14 (the confirm-password page) is unaffected and stays buildable. The only passkey references in the frontend today are generated route helpers; nothing hand-written imports them, and no test touches them.

**Blocked by:** 17 — Return throttling to Fortify (shares the same config and provider files; sequenced to avoid overlapping edits).

**Status:** ready-for-agent

- [ ] The passkeys feature is removed from the Fortify features list
- [ ] The passkeys configuration block and its limiter name are removed
- [ ] Generated route helpers are regenerated and no longer emit passkey files
- [ ] The password-confirmation routes still exist, leaving ticket 14 buildable
- [ ] Browser smoke tests pass, and Pint / Larastan / ESLint / Prettier / vue-tsc stay clean
