# 09 — Persian server messages for auth flows

**What to build:** Server-generated text in the auth flows becomes Persian. Flip the app locale to `fa` (fallback `en`) and hand-roll the Persian lang files these flows surface: the auth failure/throttle lines, the password-broker status lines, the validation rules and attribute names the auth forms use, and the JSON translation file covering Fortify's literal strings and both notification-email sentences (subject and body are literal-English JSON keys — populating them is what makes the emails Persian; fallback locale never applies to JSON keys). Demoable immediately on the existing login page: a failed login speaks Persian.

Consult the research findings on branch `research/persian-auth-messages` (`docs/research/persian-auth-messages.md`) for the exact keys each flow reads. No new composer dependency.

**Blocked by:** None — can start immediately.

**Status:** done

- [x] A failed login shows the Persian auth-failure message; throttled logins show the Persian throttle message
- [x] Validation errors on the login form (and the rules the coming auth forms use, including the default password rule failures) render in Persian, with Persian attribute names
- [x] The password-broker status lines and Fortify's literal strings (e.g. the incorrect-password confirm message) have Persian translations in place
- [x] Both notification emails' sentences are translated in the JSON lang file (asserted when tickets 12/13 exercise them)
- [x] Existing feature tests updated where they asserted English text, and green

**Note on the throttle line:** the route-level `throttle:login` middleware trips before Fortify's
login pipeline, and its `Too Many Attempts.` is a hardcoded English literal in
`ThrottleRequests::buildException()` that no lang file can reach. The `login` limiter in
`FortifyServiceProvider` therefore attaches a `->response(...)` callback returning the Persian
`auth.throttle` line as a field error (429 + rate-limit headers for JSON clients, redirect-back
with the error inline for browsers). Limits themselves are unchanged. Fortify's verification-resend
route ships `throttle:6,1` with the same hardcoded literal — it will need the identical treatment
in ticket 13.

**Note on tests:** by explicit instruction, no tests were written for the language files. The
Persian output was verified by hand over HTTP instead. Nothing currently guards these strings
against regression — in particular the throttle path, which the browser smoke seam (ticket 10)
does not cover either.
