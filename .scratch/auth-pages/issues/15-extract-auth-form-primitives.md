# 15 — Extract the auth form field & form-card primitives

**What to build:** Pull the duplicated form markup out of the four auth pages into shared primitives, so the remaining pages stop copying it. A `TextField.vue` component owns the label/input/error block along with its `aria-invalid`, `aria-describedby`, and `<field>-error` wiring, which was hand-repeated nine times. Two Tailwind v4 `@utility` classes in `app.css` carry the styling: `auth-form` for the card, and `field-input` for the control — the latter hanging its error treatment off `&[aria-invalid='true']` so the styling can't drift from the attribute. The byte-identical `inputClasses()` helper in all four pages goes away. Prefactor — no new behavior, no visual change. The spec anticipated this at `spec.md:50`.

**Blocked by:** None — landed after 12.

**Status:** done

- [x] `TextField.vue` renders the label/input/error block and owns the ARIA wiring, with a `label-action` slot for Login's forgot-password link
- [x] `auth-form` and `field-input` utilities live in `resources/css/app.css`; no page defines `inputClasses` any more
- [x] Login, Register, ForgotPassword, and ResetPassword all render through the primitives, with page shell, headings, status banner, and submit button left untouched
- [x] The four browser smoke tests and the three auth feature tests pass unchanged

## Comments

- Per the user, no Pest test was written for the Vue component. The existing browser smoke tests remain the regression net; the ARIA wiring and the `field-input` CSS are not directly asserted anywhere.
- One intentional DOM delta: the two `password_confirmation` inputs previously rendered no `aria-invalid` attribute and now render `aria-invalid="false"`. Inert against the CSS, which only matches `'true'`.
- `resources/js/lib/utils.ts` (`cn`) is now unused. Left in place — pruning it and its `clsx`/`tailwind-merge` dependencies is a separate call.
