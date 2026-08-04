# 01 — Save bank details

**What to build:** Somewhere for an owner to tell the app where their money should go: a Sheba and the name on the account, saved once and reused for every withdrawal. Typing a 26-character IBAN afresh each time is exactly how money reaches a stranger, so it is stored — but it belongs to the user, and later withdrawals will snapshot it rather than point at it, so editing it can never rewrite where past money went.

No owner-facing settings surface exists yet. This ticket creates one, in the app's Persian RTL language, following the auth pages' established card pattern.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] A signed-in user can save a Sheba and an account-holder name
- [ ] The Sheba is validated for shape — the `IR` prefix and the correct digit length — with a Persian error when it is wrong
- [ ] Shape validation is the only check; there is no bank-account verification service and none is added
- [ ] A user can change their bank details later
- [ ] A user with no bank details saved sees the form empty, with no error, rather than being told something is missing
- [ ] Bank details are visible only to the user they belong to, and to an admin
- [ ] One user cannot read or change another user's bank details
- [ ] A guest is redirected to login
- [ ] A browser smoke test visits the screen and asserts no JavaScript errors
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
