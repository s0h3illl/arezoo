# 13 — Email verification end-to-end

**What to build:** Email verification switched on and woven through. A newly registered user now lands on the verify-email notice (custom register response) showing which address the link went to, with a resend button (throttled, Persian feedback) and a logout link. The Persian verification email (text from ticket 09, RTL layout from ticket 12) carries the signed link; clicking it marks the user verified and takes them into the app. Already-verified users visiting the notice are redirected in. The User model becomes verifiable (the contract import is already staged, commented out). Verification gates nothing yet — the policy (verified required to publish a Wish or make a Contribution, never to browse) binds future routes.

**Blocked by:** 11 — Register page; 12 — Forgot & reset password flow.

**Build on:** 15 — the shared `TextField.vue` component and the `auth-form` / `field-input` utilities. Any form on the notice page uses those rather than copying Login's markup.

**Status:** done

- [x] Fortify's email verification feature is enabled and the User model implements the verification contract
- [x] Registering sends the Persian verification email (RTL layout) and redirects to the notice page, which shows the target address, resend button, and logout link
- [x] Resending shows Persian sent-feedback; hammering it hits the throttle with Persian feedback
- [x] The emailed link marks the user verified and redirects into the app; already-verified visitors to the notice are redirected in
- [x] Factory verified/unverified states are used by the tests
- [x] Feature tests cover notice render, resend, throttle, the verify link, and the register redirect; a browser smoke test covers the notice page

## Comments

**Throttle feedback reads page props, not the form slot.** The `verification` limiter guards the resend
button *and* the emailed link. Following the link is a plain GET, so Inertia's `<Form>` never runs its
`onError` and its slot error bag stays empty — the notice would have rendered no feedback at all on the
path a mail client actually takes. `VerifyEmail.vue` therefore reads `errors.verification` off the page
props (Inertia always shares them), which covers both paths. Guarded by a browser test that was
confirmed red before the fix.

**Named limiter, shipped behaviour.** Fortify ships `throttle:6,1` on both verification routes; the app
registers a named `verification` limiter purely to answer in Persian, keeping the shipped 6/minute rate
and the shipped per-user key (spec: "Rate limiting stays at Fortify/framework defaults — only the
user-facing feedback becomes Persian").

**`SubmitButton.vue` extraction.** Cashes in ticket 12's review note ("worth extracting shared
`AuthInput`/`SubmitButton` components when the next auth page — ticket 13 or 14 — touches this
pattern"), so all five auth pages now share it alongside ticket 15's `TextField`.

**Throttle assertions are deliberately loose.** Per the user, the tests assert only that an error is
present, not its wording — `lang/fa/verification.php` owns the sentence.
