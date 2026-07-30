# 09 — Persian server messages for auth flows

**What to build:** Server-generated text in the auth flows becomes Persian. Flip the app locale to `fa` (fallback `en`) and hand-roll the Persian lang files these flows surface: the auth failure/throttle lines, the password-broker status lines, the validation rules and attribute names the auth forms use, and the JSON translation file covering Fortify's literal strings and both notification-email sentences (subject and body are literal-English JSON keys — populating them is what makes the emails Persian; fallback locale never applies to JSON keys). Demoable immediately on the existing login page: a failed login speaks Persian.

Consult the research findings on branch `research/persian-auth-messages` (`docs/research/persian-auth-messages.md`) for the exact keys each flow reads. No new composer dependency.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] A failed login shows the Persian auth-failure message; throttled logins show the Persian throttle message
- [ ] Validation errors on the login form (and the rules the coming auth forms use, including the default password rule failures) render in Persian, with Persian attribute names
- [ ] The password-broker status lines and Fortify's literal strings (e.g. the incorrect-password confirm message) have Persian translations in place
- [ ] Both notification emails' sentences are translated in the JSON lang file (asserted when tickets 12/13 exercise them)
- [ ] Existing feature tests updated where they asserted English text, and green
