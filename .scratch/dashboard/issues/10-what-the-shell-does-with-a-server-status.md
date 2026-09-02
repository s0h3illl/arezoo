# 10 — What the shell does with a server `status`

Type: grilling
Status: resolved
Blocked by: none

## Question

Graduated from the map's fog once [The account menu in the header](06-the-account-menu-in-the-header.md) settled what else changes in the shell — `AppLayout` is now getting a `<ConfigProvider dir="rtl">` anyway, so the question of what *else* belongs in the shell is live rather than hypothetical.

Today `auth/VerifyEmail.vue` maps its own `status === 'verification-link-sent'` sentinel to Persian copy, inline, as a green panel on the page. Every other page raises its toasts client-side from its own form state, through `useToast`. Nothing reads a server `status` centrally.

The dashboard adds three pages and two Fortify endpoints (`PUT /user/profile-information`, `PUT /user/password`) whose successes need saying. Decide:

- **Whether `AppLayout` grows a `status` → toast mapping at all**, or whether every dashboard page keeps raising its own toast from `onSuccess`. The case for the shell shrank when [How an email change is carried](02-how-an-email-change-is-carried.md) reverted to stock Fortify and stopped inventing sentinels — the remaining sentinels are Fortify's own (`profile-information-updated`, `password-updated`, `verification-link-sent`).
- **If it is centralised, where the Persian copy lives.** A sentinel → string map in the layout is a lookup table of untranslated keys; the repo has no i18n layer and [Don't build a CMS for static copy](../../CONTEXT.md) reasoning says a file beats a table.
- **What happens to `VerifyEmail.vue`'s green panel.** It is a persistent panel, not a toast; a toast that vanishes may be the wrong shape for «we just emailed you», which is exactly the moment a user looks away at their inbox.
- **Whether a failed save needs the same path.** Fortify returns validation errors, not a status; the profile form shows them inline. Confirm the two do not need one mechanism.

Feeds the spec's Testing Decisions alongside [How the profile form is tested](09-how-the-profile-form-is-tested.md), since a shell-level mapping is asserted differently from a per-page toast.

## Answer

**The shell says «saved», and it rides on Inertia v3 flash data — not on the shared `status` prop, which is removed.**

### 1. `AppLayout` owns every server success message

One place turns a server success into a toast. No dashboard page writes toast code for a successful save, and no page maps a sentinel. The toaster and `raiseToast()` already exist (`resources/js/composables/useToast.ts`, `components/AppToaster.vue`); until now only `useShare.ts` called them and **no form success anywhere did**. This closes that.

### 2. The transport is flash data, and `status` leaves `share()`

`status` is deleted from `HandleInertiaRequests::share()` (`app/Http/Middleware/HandleInertiaRequests.php:47`). It was a *shared* prop, and shared props are persisted in browser history state — two real bugs follow from keeping it:

- **History replay.** Save → toast → navigate away → Back → the toast fires again from the restored history entry.
- **Repeat save.** Two identical saves flash the identical string, so a `watch` on the value never re-fires and the second save says nothing.

Inertia v3's flash data is documented for exactly this ("one-time notifications like toast messages"), is explicitly not persisted in history, and fires a global `flash` event on **every** response carrying it. Both bugs go away by construction rather than by workaround.

```js
// AppLayout.vue
router.on('flash', (event) => {
    if (event.detail.flash.toast) raiseToast(event.detail.flash.toast)
})
```

### 3. The Persian copy is Persian before it leaves the server

A new **`lang/fa/fortify.php`**. The frontend gets no lookup table and no untranslated keys — the objection in the Question stands, but the answer is not "per-page copy", it is "translate at the source". The Question's claim that the repo has no i18n layer is **wrong**: `lang/fa/` already holds `auth.php`, `passwords.php`, `validation.php` and `contributions.php`, with `APP_LOCALE=fa`.

### 4. Five Fortify responses are rebound — a chosen cost, stated plainly

All are container singletons bound to contracts in Fortify's own `FortifyServiceProvider`, so this is the published extension point, not an invented mechanism. **Three** flash a bare English constant today and gain translated copy:

- `ProfileInformationUpdatedResponse`
- `PasswordUpdateResponse`
- `EmailVerificationNotificationSentResponse`

**Two** already flash `trans($this->status)` and move to flash data only because the shared prop they landed on is gone:

- `PasswordResetResponse`
- `SuccessfulPasswordResetLinkRequestResponse`

Without these two, Login and ForgotPassword go silent. This is more server surface than [How an email change is carried](02-how-an-email-change-is-carried.md) took on when it reverted to stock Fortify, and the spec records it as a **decision with a price** — five small classes — rather than slipping it in. The alternative was English keys and Persian strings in a Vue file.

### 5. The three green panels

- **`auth/Login.vue`** — panel dropped. Its status is a completed password reset («رمز عبور شما تغییر کرد.»); the user is present and the action is over. Toast is strictly better.
- **`auth/VerifyEmail.vue`** — panel dropped, and the earlier worry about a vanishing «we emailed you» does **not** apply. The page already states the durable fact unconditionally: «یک لینک تأیید برات فرستادیم» in the header and «لینک به این نشانی رفت» with the address below. The panel only ever marked the **resend** — an event, which is a toast's shape. The inline `status === 'verification-link-sent'` sentinel mapping disappears with it.
- **`auth/ForgotPassword.vue`** — **keeps its panel**, now reading `page.flash`. Its header is an instruction («ایمیلت رو بنویس تا…»), not a confirmation, and the form stays on screen after submit, so the panel is the *only* evidence the link was sent. It opts out of the shell toast.

### 6. The opt-out is Inertia v3 Layout Props, not a new mechanism

Confirmed against the docs: `[Layout, {props}]` is a tuple with static props, distinct from the bare `[SiteLayout, NestedLayout]` nesting form — Inertia tells them apart by component-vs-object. Defaults live on the layout, so every page that says nothing gets the default. No page in the repo uses the tuple yet and no layout declares props; this is the first, and it is a documented v3 feature.

```vue
// AppLayout.vue                          // ForgotPassword.vue
withDefaults(defineProps<{                defineOptions({
    toastStatus?: boolean                     layout: [AppLayout, { toastStatus: false }],
    toastErrors?: boolean                 })
}>(), { toastStatus: true, toastErrors: false })
```

### 7. Failed saves toast too — opt-in, not app-wide

The Question asked whether to confirm the two paths stay separate. **They do not** — a failed save toasts as well, but errors cannot use flash: Inertia never returns 422, it surfaces validation errors as `page.props.errors` and fires `router.on('error')`.

The listener lives in `AppLayout` and is gated by `toastErrors`, defaulting **false**. The dashboard's three pages opt in; `Login`, `Register` and the contribute form are untouched and keep inline errors alone. Ungated, every short form in the app would toast «ذخیره نشد» while the field error sat in plain view. The motivation is specifically the long account form, where an error can fall below the fold.

### What this changes elsewhere

- **[How the profile form is tested](09-how-the-profile-form-is-tested.md) is amended.** It budgeted five browser tests and no toast assertions. A shell-level mapping is asserted **once** against the layout rather than per page, and the flash transport is feature-testable server-side through Inertia's flash testing helpers — which a per-page `onSuccess` toast would not have been.
- **`.scratch/auth-pages/` gains a fourth amendment.** This map now edits three of its pages (`Login.vue`, `VerifyEmail.vue`, `ForgotPassword.vue`), on top of the email-change form already owed to `VerifyEmail.vue`. [Write the spec](08-write-the-spec.md) must carry all four.
- **`ContributeForm` and the wish forms are explicitly untouched.** They opt into neither prop and keep the behaviour they have.


## Comments

**Facts found while claimed, session abandoned before any decision was put.** Three of the premises in the Question above are wrong, and correcting them changes the shape of the decision:

- **`status` is already a globally shared prop.** `HandleInertiaRequests::share()` shares `session('status')` on every response (`app/Http/Middleware/HandleInertiaRequests.php:47`). `auth/VerifyEmail.vue`, `auth/Login.vue` and `auth/ForgotPassword.vue` do not receive it from a controller — they redeclare a prop that already reaches them. Centralising is not about plumbing a prop; the prop is there and three pages read it.
- **The repo does have an i18n layer.** `lang/fa/` holds `auth.php`, `passwords.php`, `validation.php`, `contributions.php`, and `APP_LOCALE=fa`. `Login.vue` and `ForgotPassword.vue` print `{{ status }}` raw *because it is already Persian* — Fortify's password-broker responses flash `trans($this->status)` through `lang/fa/passwords.php`. Only `VerifyEmail.vue` maps a sentinel, and only because its response flashes a bare constant.
- **The sentinels are sentinels only because nobody rebound the response.** `ProfileInformationUpdatedResponse`, `PasswordUpdateResponse` and `EmailVerificationNotificationSentResponse` flash `Fortify::PROFILE_INFORMATION_UPDATED` etc., but all three are container singletons bound to contracts in Fortify's own `FortifyServiceProvider` — the documented extension point. Binding them to flash `trans(...)` would make the app's three sentinels behave exactly as the password broker's already do, putting the copy in `lang/fa/` and leaving the frontend with no lookup table of untranslated keys.

So the third option — *neither* a sentinel map in the layout *nor* per-page `onSuccess` toasts, but already-Persian copy from the server and a layout that toasts any non-empty `status` — was never on the ticket and should be weighed. Unasked when the session ended: which of the three, where `VerifyEmail.vue`'s persistent green panel goes (a toast may be the wrong shape for «we just emailed you»), whether Login/ForgotPassword's own panels would double-render against a shell toast, and whether failed saves share the path.


## Amendment — `status` is Fortify's, the toast is ours

**Overruled by the user, 2026-09-02.** The Answer above is superseded wherever it touches `status`. It is kept for its reasoning, not as instruction.

**The rule, in one line: wherever Fortify forces a `status`, use `status` and change nothing. Where the endpoint is our own code, use a toast.**

### What that means for `status`

`HandleInertiaRequests::share()` keeps sharing it, unedited. No Fortify response class is rebound. No `lang/fa/fortify.php` is written. `auth/Login.vue`, `auth/ForgotPassword.vue` and `auth/VerifyEmail.vue` are untouched, keep their green panels, and `VerifyEmail.vue` keeps its `status === 'verification-link-sent'` sentinel. The email-change form that [The email change on the verify notice](15-the-email-change-on-the-verify-notice.md) adds to `VerifyEmail.vue` is a separate change to the same file and is unaffected.

`AppLayout` gains nothing from this decision — no flash listener, no `status` mapping, no layout props.

### Which side of the line each save falls on

Checked against `route:list`, not assumed:

| Surface | Endpoint | Feedback |
| --- | --- | --- |
| Account screen — name, username, email, bio | Fortify `PUT /user/profile-information` | `status` |
| Account screen — avatar | same endpoint, posts with the profile form | `status` |
| Account screen — password | Fortify `PUT /user/password` | `status` |
| Inbox | read-only, nothing saves | neither |
| Account menu | navigation only | neither |
| `POST /wishes` | `WishController@store` — ours | toast |

**So this map's dashboard raises no toast at all.** Every save it adds runs through a Fortify endpoint, so every one of them reports through `status`, rendered the way the auth pages already render it. The toast half of the rule governs our own controllers — today only `WishController` — and the client-side toasts `useShare.ts` already raises.

None of our own controllers flash `status` today, so the two mechanisms do not overlap anywhere and never need a precedence rule.

### Consequences, stated so they are not rediscovered as bugs

- The account screen renders a `status` panel, not a toast.
- **Its two sentinels are mapped to Persian in the Vue file, not on the server.** `ProfileInformationUpdatedResponse` and `PasswordUpdateResponse` flash the bare English constants `profile-information-updated` and `password-updated`; printing either raw shows English to a Persian user. `auth/VerifyEmail.vue:34` already sets the precedent — it matches `status === 'verification-link-sent'` inline and renders its own Persian copy. The account screen does the same for its two. This is not a change to `status`: the sentinel Fortify flashes is used exactly as flashed, and only the rendering is ours.
- The history behaviours the Answer set out to fix are **accepted, not solved**: `status` is a shared prop, so a Back navigation can restore a panel, and a repeat save re-flashes an identical string. This is how the auth pages behave today and is now the dashboard's behaviour too.
- A failed save shows its inline field error and nothing else. There is no error toast anywhere in this map.
- Success feedback on the account screen is asserted the way a rendered panel is asserted — in the browser, on the page — not through a flash payload at the HTTP boundary.
