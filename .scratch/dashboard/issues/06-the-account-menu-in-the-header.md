# 06 — The account menu in the header

Type: prototype
Status: resolved
Blocked by: none

## Question

[Charting](00-charting.md) replaced the header's user icon with a dropdown carrying صفحه من / اطلاعات من / پیام‌ها (badge) / برداشت‌ها / خروج, and made it the **only** way between dashboard sections — no inline section nav, no `DashboardLayout`. It is therefore the entire navigation model for this effort, and the one piece worth seeing before it is specced.

Build it as a throwaway prototype against the real `AppHeader`, and answer:

- **Sign-out is a `POST`.** Fortify's `logout` route takes `POST`, so the menu item cannot be a `<Link href>`. Decide what it is — an Inertia `router.post`, a `<Form>`, or `<Link method="post">` — and confirm it survives being inside a `reka-ui` menu, which manages its own focus and closes on select.
- **This is the app's first sign-out.** There is none today: the only `logout` in the whole frontend is on `auth/VerifyEmail.vue`. Check whether that page's link should now defer to this, or stay as it is because an unverified user never sees the menu.
- **What an unverified user sees.** `/dashboard/*` is behind `verified`, so three of the five items lead to a bounce. Decide whether they are hidden, shown disabled, or shown live and allowed to redirect — and note this is one of the map's open fog items.
- **RTL.** The whole app is `dir="rtl"`. Confirm the menu aligns and animates correctly on the correct side without per-instance overrides.
- **Mobile.** `AdminHeader` needed a burger and a whole second nav block to survive small screens. Check whether a five-item menu needs any of that, or whether the dropdown is already the mobile answer and the admin panel's approach simply predates it.
- **The badge inside the menu.** It appears on the trigger as well as the item, or only on one. Decide, and check the trigger still reads as a button at 38px with a badge on it — the existing icon buttons are `size-[38px]` and visually uniform across the header.
- **Whether the trigger keeps the avatar.** The header renders a `UserIcon` today, not the user's picture. Once avatars are uploadable ([How an avatar is carried](01-how-an-avatar-is-carried.md)), the trigger showing the real face is the obvious move — but it is a change to a component every page renders, so decide it here rather than letting it drift in.
- **Whether `provideHeaderNav()` still works alongside it.** The nav slot keeps carrying the current page's own anchors. Confirm a page with both — anchors *and* the account menu — does not overflow the header.

Link the prototype from this ticket as an asset. Nothing lands in `resources/js/` from this ticket.

## Answer

**Variant B — the identity card.** The trigger is the user's avatar, and the panel opens with a name / `@username` block above the items. Prototype: commit `fce8acc`, `resources/js/components/prototype/`, mounted in the real `AppHeader`, flipped by `?variant=`. Screenshots in `tests/Browser/Screenshots/`. **The branch was fast-forwarded into `main` on 2026-09-02, so the prototype components and the `<PrototypeSwitcher />` in `AppLayout` are live on `main`** — the earlier note that nothing landed there is no longer true. Retrieve any variant at any time with `git show fce8acc:resources/js/components/prototype/AccountMenuB.vue`.

### What the menu is

- A `reka-ui` **`DropdownMenu`** — the app's first, though `reka-ui` is already a dependency. Trigger is a 38px round avatar (`user.avatar`, falling back to a `bg-emerald-100` circle carrying the first letter of `name`), replacing today's bordered `UserIcon` button. This is the change [How an avatar is carried](01-how-an-avatar-is-carried.md) makes possible, and it is decided here rather than left to drift.
- Panel is 260px, aligned to the trigger, with an identity header (avatar, `name`, `@username`), then the four navigation rows each with an icon, then a separator, then **خروج از حساب**.
- Rows: **صفحه من** → `profile(user.username)`; **اطلاعات من** → `/dashboard`; **پیام‌ها** → `/dashboard/messages`; **برداشت‌ها** → `/dashboard/withdrawals`.

### Sign-out is `<Link :href="logout()">`

Wayfinder's `logout()` already returns `{ url: '/logout', method: 'post' }`, so the app's existing link on `auth/VerifyEmail.vue` is already a POST — no new mechanism is needed. All three candidates were probed inside the menu (`tests/Browser/PrototypeSignOutProbeTest.php`):

| mechanism | inside a `reka-ui` menu |
| --- | --- |
| `<Link :href="logout()">` | signs out |
| `router.post(logout.url())` | signs out |
| `<Form :action="logout()">` | **does not** — `DropdownMenuItem`'s select closes the menu and unmounts the portal before the form submits |

The `<Form>` failure is the one real trap here, and it is the shape a reader would reach for first. The spec must name `<Link>` explicitly.

**`auth/VerifyEmail.vue` keeps its own خروج link.** `AppLayout` renders on that page too, so an unverified user sees the account menu there and the word appears twice. The page link stays anyway: it is the recovery path the copy points at, and [How an email change is carried](02-how-an-email-change-is-carried.md) gives that page the email-change form beside it. The menu is the second way, not the first.

### The unverified user sees the sections, greyed — this closes the map's fog item

- An amber strip — **«ایمیلت هنوز تأیید نشده»** — sits between the identity header and the rows. Telling the user the status explicitly is the reason this variant won.
- The three `/dashboard/*` rows render **disabled**: slate-300, `pointer-events-none`, `DropdownMenuItem :disabled`, so keyboard navigation skips them too. صفحه من and خروج stay live.
- Not hidden: a user who cannot see the sections cannot learn they exist. Not live-and-bounce: with the strip already naming the cause, a redirect would be a second, quieter answer to a question already answered.

### The badge is a dot on the avatar and a count on the row

- Trigger: a rose dot (`size-[10px]`, white ring) on the avatar's edge when the count is non-zero. No numeral — a digit over a 38px face is busy, and the count would then be printed twice.
- Row: the number, as a rose pill on **پیام‌ها**.
- The dot's existence is what [What the unread badge costs on every page](05-what-the-unread-badge-costs.md) is pricing: a boolean is enough for the header, the exact count only has to be right inside the menu.

### RTL needs `<ConfigProvider dir="rtl">` in `AppLayout`

`reka-ui` reads direction from `ConfigProvider`, **not** from the DOM `dir` attribute; it defaults to `ltr`. Proven by removing `dir="rtl"` from a variant and rebuilding: `align="end"` flipped and the panel shot to the viewport's left edge, clear of its trigger. This has been invisible so far because every existing `reka-ui` use in the app — dialogs, toasts, progress — is side-agnostic.

The fix goes in **`AppLayout`**, one `<ConfigProvider dir="rtl">` wrapping the shell, not a `dir` prop per menu: the next popper-positioned primitive would otherwise repeat the bug, and the app is `rtl` everywhere.

### Mobile needs nothing, and the admin burger does not transfer

At 375px the dropdown fits with room to spare beside the logo and the landing page's two section anchors. `AdminHeader` needs a burger because it renders **six** nav items inline; a five-item menu behind one trigger has no such problem. The dropdown *is* the mobile answer. No `md:` breakpoint, no second nav block, no `MenuIcon` in `AppHeader`.

### `provideHeaderNav()` is unaffected

Probed on the landing page, which provides two anchors: header holds logo + anchors + trigger without overflow at 1728px and at 375px. `AppHeader` already drops the home icon when a page provides its own nav (`v-else`), so the account trigger is the only addition to that row.

### Also learned

A drawer variant must open on the **trigger's** side. Built right-anchored first, it opened on the opposite edge of the screen from its own button — cheap to get wrong in RTL, worth remembering if a drawer ever appears elsewhere.

## Amendment

[How the profile form is tested](09-how-the-profile-form-is-tested.md) changed the route policy after this ticket closed: **`/dashboard` is the one exception to `verified`**, running on `auth` alone, so an unverified user reaches the account screen from the header and can correct a mistyped address there. `/dashboard/messages` and `/dashboard/withdrawals` keep `auth` + `verified`.

Consequences for this ticket: the account-menu row اطلاعات من is **enabled for every signed-in user** — only پیام‌ها and برداشت‌ها stay disabled behind the amber strip — and the email form on `auth/VerifyEmail.vue` is now a **kept convenience rather than the only door**. It was kept deliberately, with the duplication understood.
