# 13 — Email verification end-to-end

**What to build:** Email verification switched on and woven through. A newly registered user now lands on the verify-email notice (custom register response) showing which address the link went to, with a resend button (throttled, Persian feedback) and a logout link. The Persian verification email (text from ticket 09, RTL layout from ticket 12) carries the signed link; clicking it marks the user verified and takes them into the app. Already-verified users visiting the notice are redirected in. The User model becomes verifiable (the contract import is already staged, commented out). Verification gates nothing yet — the policy (verified required to publish a Wish or make a Contribution, never to browse) binds future routes.

**Blocked by:** 11 — Register page; 12 — Forgot & reset password flow.

**Build on:** 15 — the shared `TextField.vue` component and the `auth-form` / `field-input` utilities. Any form on the notice page uses those rather than copying Login's markup.

**Status:** ready-for-agent

- [ ] Fortify's email verification feature is enabled and the User model implements the verification contract
- [ ] Registering sends the Persian verification email (RTL layout) and redirects to the notice page, which shows the target address, resend button, and logout link
- [ ] Resending shows Persian sent-feedback; hammering it hits the throttle with Persian feedback
- [ ] The emailed link marks the user verified and redirects into the app; already-verified visitors to the notice are redirected in
- [ ] Factory verified/unverified states are used by the tests
- [ ] Feature tests cover notice render, resend, throttle, the verify link, and the register redirect; a browser smoke test covers the notice page
