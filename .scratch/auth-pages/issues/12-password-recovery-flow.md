# 12 — Forgot & reset password flow

**What to build:** The full recovery loop. From login, a user reaches the forgot-password page, submits their email, and gets Persian feedback (honest Persian error for an unknown email — no anti-enumeration masking). The reset email arrives in Persian (its text comes from ticket 09's JSON translations) inside an RTL-friendly mail layout — this ticket publishes the vendor mail views and establishes that layout, which ticket 13 reuses. The emailed link opens the reset page with the email pre-filled; a new password plus confirmation lands the user on login with a Persian success flash; expired or tampered tokens show a clear Persian error. Both pages use Login's card pattern.

**Blocked by:** 08 — Land the Login layout refactor; 09 — Persian server messages; 10 — Browser smoke seam.

**Status:** ready-for-agent

- [ ] Forgot-password page renders in the card pattern, reachable from login's existing link
- [ ] Submitting a known email sends the reset notification and shows the Persian status flash; an unknown email shows the Persian validation error
- [ ] The reset notification's subject and body are Persian, rendered in an RTL mail layout (vendor mail views published, direction set)
- [ ] The reset page renders from the emailed link with the email pre-filled; valid submission resets the password and redirects to login with a Persian flash; no auto-login
- [ ] An expired or invalid token shows a clear Persian error
- [ ] Feature tests cover the whole loop with the notification fake (including Persian subject assertion); browser smoke tests cover both pages
