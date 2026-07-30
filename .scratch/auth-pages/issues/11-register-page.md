# 11 — Register page

**What to build:** A visitor creates an account. The register route renders a Persian card page in Login's pattern (same layout, wash, card, input/error styling) asking name, email, password, and password confirmation; submitting creates the user and authenticates them; validation failures show inline Persian errors; a link switches to login. Registration redirects to Fortify's default home for now — ticket 13 repoints it to the verify-email notice.

**Blocked by:** 08 — Land the Login layout refactor; 09 — Persian server messages; 10 — Browser smoke seam.

**Status:** ready-for-agent

- [ ] The register route renders the register page in the shared layout and card pattern, with a link to login
- [ ] Submitting valid name/email/password/confirmation creates the user and authenticates them
- [ ] Invalid input (missing fields, duplicate email, weak password, mismatched confirmation) shows inline Persian errors on the right fields
- [ ] Authenticated users are redirected away from the register page
- [ ] Feature tests in the existing authentication-test style cover render, success, and failure paths; a browser smoke test covers the page
