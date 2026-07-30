# 11 — Register page

**What to build:** A visitor creates an account. The register route renders a Persian card page in Login's pattern (same layout, wash, card, input/error styling) asking name, email, password, and password confirmation; submitting creates the user and authenticates them; validation failures show inline Persian errors; a link switches to login. Registration redirects to Fortify's default home for now — ticket 13 repoints it to the verify-email notice.

**Blocked by:** 08 — Land the Login layout refactor; 09 — Persian server messages; 10 — Browser smoke seam.

**Status:** done

- [x] The register route renders the register page in the shared layout and card pattern, with a link to login
- [x] Submitting valid name/email/password/confirmation creates the user and authenticates them
- [x] Invalid input (missing fields, duplicate email, weak password, mismatched confirmation) shows inline Persian errors on the right fields
- [x] Authenticated users are redirected away from the register page
- [x] Feature tests in the existing authentication-test style cover render, success, and failure paths; a browser smoke test covers the page

## Comments

**The server side was already done.** Only `Fortify::registerView()` was missing — Fortify's registration feature is enabled, `CreateNewUser` already validates name/email/password with `confirmed` in the password rules, and ticket 09's lang files already render every message in Persian. Seven of the eight feature tests passed before a line of implementation was written; the eighth was the unbound view. Nothing in `CreateNewUser` needed changing.

**No client-side pre-validation, unlike Login.** `Login.vue` checks for an empty email and a short password in `submit()` before posting, with its own hardcoded Persian strings. Register relies on server-side validation alone, so the messages have one source of truth (`lang/fa/validation.php`) and can't drift from it. Worth reconciling Login to match at some point — its inline literals already disagree with the server on digit shape (see below).

**Known cosmetic mismatch — Persian vs Latin digits.** The server's min-length message renders `:min` as a Latin `8` ("رمز عبور باید دست‌کم 8 کاراکتر باشد."), while `Login.vue`'s hardcoded client-side string uses the Persian `۸`. Out of scope here — it's ticket 09's surface — but a user who trips both paths sees both digit shapes. Flagging rather than fixing.

**A mismatched confirmation reports on the password field, by framework design.** Laravel's `confirmed` rule always attaches its error to `password`, never to `password_confirmation`. The first draft of this page carried an error block, `aria-invalid`, and `aria-describedby` bound to `form.errors.password_confirmation` — all of it unreachable. That markup is gone and a comment marks why, so nobody restores it. The consequence to live with: mistype the confirmation and the red ring plus message land on the field above, while the offending input stays neutral. The message names the problem ("رمز عبور با تکرارش یکی نیست.") so it is navigable, but it is not ideal for screen-reader users (spec story 29). Fixing it properly means validating `password_confirmation` with `same:password` in its own right, which touches `PasswordValidationRules` — shared with password reset and the profile password update — and so belongs to a ticket that owns those flows, not this one.

**Duplication deliberately left in place — and it is larger than one helper.** `Register.vue` repeats `Login.vue`'s `inputClasses()` helper, its label/input/error field markup, the headline block, the `<form>` shell classes, the submit button with its spinner SVG, and the footer link — close to 85% of the file. The spec permits extraction "only if duplication across the five pages makes it obviously right; not a requirement", and refactoring `Login.vue` is outside this ticket. At two pages it is a judgement call; by ticket 12 (forgot + reset password) it will be four pages, and that is the moment to extract. Two notes for whoever does it: the natural unit is an auth-card shell plus a field component, not a field component alone; and the pages are not identical — Login carries the client-side pre-validation described above and a "forgot password?" link beside its password label — so the field needs a slot for the label-adjacent action. The digit-shape mismatch above is already an instance of the drift this duplication produces.

**The browser smoke tests are one file per page**, per the seam ticket 10 established. The Pest docs also document a multi-page form (`visit(['/login', '/register', ...])->assertNoJavaScriptErrors()`), which would collapse tickets 10–14's five near-identical files into one. Worth reconsidering at ticket 12 rather than growing five clones.
