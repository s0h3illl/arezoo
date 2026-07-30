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

**Duplication deliberately left in place.** `Register.vue` repeats `Login.vue`'s `inputClasses()` helper and its label/input/error field markup verbatim — roughly 30 lines per field. The spec permits extracting a shared field component "only if duplication across the five pages makes it obviously right; not a requirement", and refactoring `Login.vue` is outside this ticket. At two pages and six fields it is a judgement call; by ticket 12 (forgot + reset password) it will be four pages, and that is the moment to extract. Note that the two pages are not identical — Login carries the client-side pre-validation described above and a "forgot password?" link beside its password label — so the component needs a slot for the label-adjacent action.
