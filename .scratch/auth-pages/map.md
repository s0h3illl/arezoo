# Auth pages

Label: wayfinder:map

## Destination

A build-ready spec at `.scratch/auth-pages/spec.md` covering the five missing auth surfaces — Register, Forgot Password, Reset Password, Confirm Password, and Verify Email (feature enabled) — each defined as deltas on Login's established card pattern, plus Persian server-side messages (validation errors and the two notification emails) for these flows. Implementation is a separate effort that starts only on the user's explicit go.

## Notes

- Persian, RTL wishlist app ("Arezoo") — use `CONTEXT.md` vocabulary. UI copy is hardcoded Persian in the Vue pages.
- Visual design: no new design artifacts. Every page is a delta on Login's card — emerald radial wash, 400px centered card, `AppLayout` slim header (see `resources/js/pages/auth/Login.vue` and `login-spec.html`).
- Standing order: **do not implement anything** until the user explicitly says so — this map produces decisions and the spec only.
- Decision tickets run via `/grilling` (+ `/domain-modeling` when terms surface). Codebase reading: `inertia-vue-development`, `tailwindcss-development`, `laravel-best-practices` skills.
- Fortify facts (verified 2026-07-31): features on = registration, resetPasswords, updateProfileInformation, updatePasswords, passkeys(confirmPassword); `emailVerification` commented out; two-factor absent entirely. Only `loginView` is bound to an Inertia page. `CreateNewUser` validates name/email/password. No `lang/` directory; `APP_LOCALE=en`; `MAIL_MAILER=log`.

## Decisions so far

<!-- one line per closed ticket: gist + link -->

- [Persian server-message options](issues/05-persian-server-messages-research.md) — fa files only fire when the locale is fa; laravel-lang's fa pack covers all Fortify/validation/email strings on Laravel 13, emails go Persian via lang/fa.json alone, and RTL email markup needs published vendor mail views (full findings on branch `research/persian-auth-messages`, `docs/research/persian-auth-messages.md`).

- [Register page decisions](issues/01-register-page-decisions.md) — name/email/password/confirmation, no terms; post-register → Verify Email notice.
- [Email verification rollout](issues/02-email-verification-rollout.md) — verification gates publishing a Wish / making a Contribution, never browsing; notice page has resend + logout.
- [Forgot & reset password flow decisions](issues/03-password-reset-flow-decisions.md) — Fortify defaults Persian-ized; honest errors, reset lands on login with flash.
- [Confirm password page decisions](issues/04-confirm-password-page-decisions.md) — dedicated page in the Login card pattern, intended-URL return.
- [Localization approach decision](issues/06-localization-approach-decision.md) — hand-rolled `lang/fa`, `APP_LOCALE=fa` + `en` fallback, emails via notification callbacks; consult research branch when building.
- [Assemble the auth-pages spec](issues/07-assemble-the-spec.md) — **destination reached**: [spec](spec.md) published `ready-for-agent` via `/to-spec`, fast-forwarding tickets 01–04 and 06.

## Not yet specified

- Where authenticated users land (`'home' => '/'`; post-login/register/reset redirects) may shift once the Landing and Profile efforts produce real destinations. The spec should mark redirect targets provisional; this sharpens when those pages land.

## Out of scope

- Two-factor authentication — explicitly excluded by the user; not enabled in Fortify config.
- Passkey registration/management UI — belongs to the Profile/settings effort (design Spec 3/3).
- Production mail provider setup — deployment concern; dev uses the log mailer.
- App-wide Persian localization — this effort covers only auth-flow validation messages and the two auth notification emails.
