# 14 — Confirm password page

**What to build:** The sensitive-action interrupt. When a signed-in user triggers something guarded by password confirmation (today: confirming a passkey), they get a dedicated Persian card page — not a modal — asking for their current password. A correct password returns them to what they were doing (Fortify's intended-URL handling); a wrong one shows the Persian inline error (Fortify's literal string, translated in ticket 09) and another attempt.

**Blocked by:** 08 — Land the Login layout refactor; 09 — Persian server messages; 10 — Browser smoke seam.

**Status:** ready-for-agent

- [ ] The confirm-password route renders the page in the shared layout and card pattern for authenticated users
- [ ] A correct password confirms and returns the user to the intended action
- [ ] A wrong password shows the Persian error inline and allows retry
- [ ] Feature tests cover render, success (including intended-URL return), and failure; a browser smoke test covers the page
