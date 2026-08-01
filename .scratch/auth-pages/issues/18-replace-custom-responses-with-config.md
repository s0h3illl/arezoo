# 18 — Replace the custom response classes with config and the status slug

**What to build:** The last two bespoke Fortify response classes disappear, replaced by one config line and one client-side string.

**Register redirect:** Fortify reads a configurable redirect target for registration and only falls back to the home path when it is unset. Our published Fortify config predates that section, which is why a whole response class was written to do what one key does. Add the section, point registration at the verification notice, delete the class.

**Resend feedback:** Fortify's shipped response already flashes a `status` of `verification-link-sent` — a slug, deliberately left for the client to word. Our class overrode it purely to flash a Persian sentence from the server, which cuts against this project's own convention that UI copy is hardcoded Persian in the Vue pages. Render the Persian from the slug on the notice page and delete the class, its binding, and the lang file that existed only to hold that sentence.

The notice page also sheds the error prop added for the verification throttle: with ticket 17 moving that message to a full error page, no inline error can reach the form, so the binding is dead code.

Finally, restore the shipped guard in the profile-information action that was dropped while wiring verification. It is behaviour-identical now that the user model implements the contract, but keeping the action diffable against upstream is worth more than the saved line.

**Blocked by:** 16 — Drop the Fortify-covered feature tests.

**Status:** done

- [x] `app/Http/Responses/` is deleted along with the container bindings that registered both classes
- [x] Registration redirects to the verification notice via Fortify's redirects config, not app code
- [x] The notice page shows the Persian sent-confirmation, driven by Fortify's shipped status slug
- [x] The notice page no longer declares or renders a verification error
- [x] The verification lang file is deleted, with no dangling references
- [x] The profile-information action matches the shipped implementation again
- [x] The Fortify service provider is left with only its action bindings and view bindings
- [x] Browser smoke tests pass, and Pint / Larastan / ESLint / Prettier / vue-tsc stay clean

## Comments

- `redirects.register` is set to the raw path `/email/verify`, not `route('verification.notice')`. Config files load before Fortify's routes are registered, so a `route()` call here would fail — Fortify's own docs example (`LogoutResponse`) uses the same raw-string convention.
- Larastan flags the restored `$user instanceof MustVerifyEmail` check as always-true, which is correct in this codebase — `App\Models\User` always implements the contract. Added a scoped `ignoreErrors` entry in `phpstan.neon` rather than an inline suppression, since the check is intentionally kept for upstream diffability, not a bug.
- The "notice page sheds the error prop" checklist item was already satisfied by ticket 17 — `VerifyEmail.vue` never regained an error prop after that work, so nothing to remove here.
