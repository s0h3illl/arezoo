# Spec: Auth pages

Status: ready-for-agent

## Problem Statement

Only the login page exists. A visitor cannot create an account; a user who forgets their password has no way back in; sensitive actions that require re-entering a password (today: passkey confirmation) have no screen to do it on; and nothing verifies that a user's email address is real. Fortify already registers every one of these routes — visiting them errors because no view is bound. On top of that, the UI speaks Persian while everything server-generated (validation errors, the reset and verification emails) is English.

## Solution

Five Persian, RTL auth pages in Login's established visual pattern — Register, Forgot Password, Reset Password, Confirm Password, and Verify Email — with email verification enabled, so a user can sign up, recover access, confirm their identity for sensitive actions, and prove their email address, all in one consistent voice. Server-generated messages and the two auth emails become Persian for these flows.

## User Stories

1. As a visitor, I want a registration page, so that I can create an account and start publishing wishes.
2. As a visitor registering, I want the form to ask only my name, email, password, and password confirmation, so that signing up is quick.
3. As a visitor registering, I want inline Persian validation errors on each field, so that I can fix mistakes without guessing what went wrong.
4. As a visitor registering, I want my email accepted case-insensitively, so that how I type it doesn't create duplicate-account confusion.
5. As a newly registered user, I want to land on the verify-email notice, so that I immediately know one step remains.
6. As a visitor on the register page, I want a link to login, so that I can switch if I already have an account.
7. As a visitor on the login page, I want the existing link to registration to work, so that the two pages form a loop.
8. As a user who forgot their password, I want a forgot-password page reachable from login, so that I can recover access on my own.
9. As a user requesting a reset, I want to enter my email and receive a Persian reset email, so that recovery happens in the language I use.
10. As a user requesting a reset, I want a Persian confirmation message after submitting, so that I know the link is on its way.
11. As a user requesting a reset with a typo'd or unknown email, I want a clear Persian error, so that I'm not left waiting for an email that will never come.
12. As a user clicking the emailed reset link, I want a reset page with my email pre-filled and fields for the new password and its confirmation, so that resetting takes seconds.
13. As a user whose reset link has expired or was tampered with, I want a clear Persian error, so that I know to request a fresh link.
14. As a user who completed a reset, I want to land on login with a Persian success message, so that I can sign in with the new password right away.
15. As a signed-in user performing a sensitive action (today: confirming a passkey), I want a confirm-password page in the same card style, so that the interruption feels like part of the app.
16. As a signed-in user who mistypes on the confirm-password page, I want a Persian error and another attempt, so that a slip doesn't strand me.
17. As a signed-in user who confirms their password, I want to be returned to what I was doing, so that the interruption costs nothing.
18. As an unverified user, I want the verify-email notice to show which address the link went to, so that I know where to look.
19. As an unverified user, I want a resend button on the notice, so that a lost email doesn't lock me out.
20. As an unverified user who just resent, I want Persian feedback that the link was sent, so that I don't hammer the button.
21. As an unverified user resending too often, I want throttling with Persian feedback, so that the system stays polite instead of erroring.
22. As an unverified user on the notice page, I want a logout link, so that I can switch accounts if I registered with the wrong address.
23. As an unverified user clicking the emailed verification link, I want to be marked verified and taken into the app, so that the flow ends where the app begins.
24. As an already-verified user visiting the notice page, I want to be redirected into the app, so that I never see a stale prompt.
25. As an unverified user, I want to browse the app freely but be asked to verify before publishing a Wish or making a Contribution, so that verification gates only what matters.
26. As a user receiving either auth email, I want Persian subject and body in an RTL-friendly layout, so that the email reads like the app does.
27. As an authenticated user, I want to be redirected away from the register, login, forgot-password, and reset pages, so that guest screens never show up mid-session.
28. As a user on any auth page, I want the same centered card, header, and emerald styling as login, so that the auth flow feels like one place.
29. As a user relying on a screen reader or keyboard, I want labeled fields, error announcements, and focusable controls matching login's accessibility patterns, so that every flow works without a mouse.
30. As an owner of the system, I want login, reset, and resend endpoints rate-limited with Persian feedback, so that abuse is blunted without confusing real users.

## Implementation Decisions

- Enable Fortify's email verification feature; the User model implements the framework's email-verification contract (the import is already staged in the model, commented out).
- Bind all five missing Fortify views to Inertia pages alongside the existing login binding: register, request-password-reset-link, reset-password, confirm-password, and verify-email.
- Every page renders inside the shared app layout (RTL, slim header) and reuses Login's card pattern verbatim: centered 400px column, emerald radial wash, headline block above the card, identical input/label/button styling and inline-error treatment. No new design artifacts — each page is a delta on Login. Extract shared form-field markup into a component only if duplication across the five pages makes it obviously right; not a requirement.
- Register submits name, email, password, and password confirmation — exactly what the existing user-creation action validates (`confirmed` is already in the password rules). No terms checkbox. Post-register, a custom register response redirects to the verify-email notice instead of home.
- Verified-email policy: verification is required for the domain's mutating actions — publishing a Wish, making a Contribution — and never for browsing. No routes exist to gate yet; the policy binds future routes via the framework's `verified` middleware.
- Forgot password keeps Fortify's standard behavior — an unknown email gets a validation error (Persian-ized), a known one gets the status flash. No anti-enumeration masking.
- Reset success follows Fortify's default: redirect to login carrying the status flash; no auto-login.
- Confirm password is a dedicated page (not a modal); Fortify's intended-URL handling returns the user to the interrupted action.
- Localization: `APP_LOCALE` flips to `fa` with `en` fallback. Hand-rolled Persian lang files scoped to what these flows surface — the validation rules and attribute names these forms use, authentication failure/throttle lines, and password-broker lines. No new composer dependency (adding one, e.g. laravel-lang, would need separate approval). The two notification emails (password reset, email verification) build their subject and body entirely from literal-English JSON translation keys, so a populated `lang/fa.json` makes them Persian with no code changes (it also covers Fortify's confirm-password string); fallback locale never applies to JSON keys, which is why the locale flip is required. RTL email markup is separate: publish the vendor mail views and set the layout direction. (Per the research findings on the `research/persian-auth-messages` branch, `docs/research/persian-auth-messages.md` — this supersedes the earlier assumption that notification customization callbacks were needed.)
- Rate limiting stays at Fortify/framework defaults (login limiter already configured; verification resend and reset-link throttles as shipped) — only the user-facing feedback becomes Persian.
- Redirect targets are provisional: home is `/` until the Landing and Profile efforts produce real destinations. Do not invent a dashboard.

## Testing Decisions

- A good test asserts external behavior at the HTTP boundary — what renders, where a POST redirects, what the session/auth state becomes — never implementation details.
- Prior art: the existing authentication feature test (Pest, Inertia component assertions on GET, flow assertions on POST, factories with the default password). Each flow gets its own feature test in the same style: registration, password reset request + reset, confirm password, email verification (notice render, resend, verify link, gating).
- The two emails are asserted with the notification fake — that the right notification goes to the right user, and that its rendered subject/content is Persian.
- Factory states cover verified vs unverified users.
- Second seam (approved): Pest 4 browser smoke tests visit each of the auth pages and assert no JavaScript errors — the repo's first browser tests, kept to smoke level.

## Out of Scope

- Two-factor authentication (explicitly excluded; not enabled in Fortify config).
- Passkey registration/management UI — belongs to the Profile/settings effort.
- Production mail provider setup — dev uses the log mailer; deployment concern.
- App-wide Persian localization beyond what these auth flows surface.
- Changing where authenticated users land after auth (stays `/`).

## Further Notes

- This spec is the destination of the wayfinder map in this directory (`map.md`); it was produced by fast-forwarding the map's open decision tickets, whose files record pointers back here.
- The login page itself is mid-refactor onto the shared layout in the working tree; the five new pages should match the refactored version, not the committed one.
- Domain vocabulary per `CONTEXT.md`: Wish, Contribution, Contributor, Owner — user-facing copy and test names should not drift to synonyms (no "donation", "pledge").
