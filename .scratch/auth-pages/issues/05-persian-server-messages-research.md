# Persian server-message options

Type: research
Status: resolved

## Question

How can server-generated text in the auth flows become Persian? Survey the options: the laravel-lang package family's `fa` translations (Laravel 13 compatibility, what it covers), hand-rolled `lang/fa` validation files, overriding Laravel/Fortify's reset-password and verify-email notification emails (Persian subject/body, RTL-friendly email markup), and locale strategy (`APP_LOCALE=fa` app-wide vs targeted lang files with the locale left `en`).

Findings land on branch `research/persian-auth-messages` as a markdown file; this ticket resolves by pointing at them.

## Answer

Full findings: branch `research/persian-auth-messages`, file `docs/research/persian-auth-messages.md`.

Gist:

- Persian text only ever comes from translation files being *loaded*, and the translator only loads `lang/fa/**` + `lang/fa.json` when the effective locale is `fa` — with `APP_LOCALE=en`, targeted `fa` files are inert unless something switches the locale (middleware, `HasLocalePreference`, `->locale('fa')` on notifications). Fallback locale only applies to PHP-file keys, never JSON keys.
- Fortify reads `auth.failed`, `auth.throttle`, `passwords.*` from PHP lang files, plus one literal JSON key ("The provided password was incorrect.", used by confirm-password/passkeys). Validation errors (incl. `Password::default()` failures) come from `validation.php`. Both notification emails (`ResetPassword`, `VerifyEmail`) build subject+body entirely from `Lang::get()` on literal English sentences — so a populated `lang/fa.json` makes them Persian with zero code changes.
- laravel-lang is Laravel-13-compatible (publisher 16.8.0 supports `illuminate ^13.0`) and its `fa` locale covers everything in these flows: all validation rules, auth/passwords lines, Fortify's literal strings, and the exact Laravel-13 email sentences. `lang:add fa` publishes, `lang:update` re-syncs. Hand-rolled `lang/fa/{auth,passwords,validation}.php` + `fa.json` works identically but the app owns translation completeness and upstream key drift.
- Email customization seams: `toMailUsing()` callbacks, overriding `send*Notification()` on User, or custom notifications. RTL markup is orthogonal to text: the default markdown mail layout has no `dir` attribute and left-aligned headings — the sanctioned fix is `vendor:publish --tag=laravel-mail` (+ optional custom markdown theme, global or per-message via `->theme()`), or plain HTML via `MailMessage->view()`.
- `APP_LOCALE=fa` flips translator strings only — Carbon, `Number`, and Faker locales are all separate switches. Combinations that actually deliver Persian validation + emails: `APP_LOCALE=fa` + fa files (simplest), or `en` + fa files + explicit locale-switching machinery.
