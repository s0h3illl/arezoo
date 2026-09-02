# 00 — The destination and the shape of the dashboard

Type: grilling
Status: resolved
Blocked by: none

## Question

A loose ask arrived: a place where a signed-in user can see and edit their own details, read the messages contributors left them, and request money out. Three things, no shape.

What is this section, what is it called, where does it live, what is on each screen, and how much of it does this map own given that `.scratch/balance-and-withdrawals/spec.md` already exists?

## Answer

### The destination

A spec for the signed-in user's own section. **`.scratch/balance-and-withdrawals/spec.md` is not re-opened** — its 48 stories already settle the withdrawal behaviour in full, and it was blocked only by the admin panel, which has since shipped. This map decides where its owner-facing screen hangs and amends it where the two conflict.

### The name and the URL

**Dashboard, at `/dashboard`.** «Profile» was unavailable — `CONTEXT.md` gives it to the public page at `/u/{username}`, the one that exists to be sent to people. `admin.dashboard` keeps its name; a root `DashboardController` sits beside `App\Http\Controllers\Admin\DashboardController` the way a root `UserController` would, since this repo separates admin by namespace rather than by name.

Three routes:

| URL | Screen |
|---|---|
| `/dashboard` | account info — name, username, email, avatar, bio, password |
| `/dashboard/messages` | messages contributors left |
| `/dashboard/withdrawals` | balance, bank details, request form, request history |

`/dashboard` is the account-info screen itself, not a landing page. A fourth overview screen of summary cards was considered and cut: every number on it is a decision, and nobody asked for it.

### The shell — there isn't one

**No `DashboardLayout`.** The three pages are ordinary `AppLayout` pages: site header, footer, toaster. The admin panel's pattern — its own layout, its own header, its own burger menu — was rejected: the admin panel is a separate world for staff, and this is not. A user in their dashboard has not left the site.

**The only chrome change is a menu.** The header's user icon becomes a `reka-ui` dropdown carrying صفحه من / اطلاعات من / پیام‌ها (with the unread badge) / برداشت‌ها / خروج. `AppLayout`'s existing `provideHeaderNav()` seam is **not** used for the sections — the menu already carries them from anywhere in the app, and a second inline nav would be the same links built twice with an active state to keep in sync. The nav slot keeps meaning what it means today: the current page's own anchors.

Two things fall out of this for free:

- **The app gets a sign-out.** There is none today. The only `logout` link in the entire frontend is on `auth/VerifyEmail.vue` — once a user is verified and signed in, nothing in the header can log them out. Fortify's `logout` is a `POST`, so the menu item submits rather than links.
- **صفحه من** points at `/u/{username}`, which is where wishes are already added, edited and deleted. The dashboard does not manage wishes.

### Gating

**`/dashboard/*` is behind `auth` + `verified`.** This goes further than `.scratch/auth-pages/spec.md`'s stated policy — "required for the domain's mutating actions… never for browsing" — and does so deliberately.

That policy is also not implemented: `wishes.store` carries only `auth`. Recorded, not this map's to fix.

Gating the whole section creates a trap, which was raised and closed rather than accepted. A user who mistypes their email at **registration** lands on the verify-email notice, which offers only resend and logout. With the dashboard fully gated they cannot reach the screen that fixes it either, and they cannot simply register again — `username` is unique and their abandoned account still holds the name they wanted. Their account and their username are both unrecoverable.

**The fix: `auth/VerifyEmail.vue` grows the email-change form**, beside its existing resend and logout. The dashboard stays fully gated. One endpoint serves both entry points. This amends `.scratch/auth-pages/`.

A **blocked** user needs no guard at all: `EnsureUserIsNotBlocked` already sits in the Fortify authenticate pipeline, so they cannot sign in.

### `/dashboard` — the account screen

Editable: **name, username, email, avatar, bio, password.**

- **Password** reuses `PUT /user/password` untouched. It already demands `current_password`, so it gates itself.
- **Name** reuses `PUT /user/profile-information`, which must stop handling email.
- **Username** is already a domain commitment — `CONTEXT.md`: "theirs to change — which frees the one they gave up, so a link shared under an old username may later lead somewhere else." `UsernameValidationRules` is already extracted.
- **Avatar and bio** close a real gap: both are rendered on the public profile (`Profile.vue:65,85`) and there is no way anywhere in the app to set them. Today they are reachable only through a factory or seeder.

**Email changes by magic link.** The new address is mailed a link; **nothing is written until it is clicked**; clicking sets the address and marks it verified in one step.

This replaces what `UpdateUserProfileInformation` does today, which is worse: it writes the new address into `users.email` immediately, nulls `email_verified_at`, and only then mails the new address. A typo therefore costs the user their verified status *and* points their account at an address they do not own — the failure is committed before it is confirmed. Under the new flow the account keeps its verified email throughout and a typo costs one wasted send.

### `/dashboard/messages`

**Only contributions that carry a message.** A contribution with a null `message` never appears. An earlier pass had the inbox showing every contribution, which collided with the balance spec's statement; both halves of that collision are now gone.

Three filters, and two of them were already decided elsewhere:

- **`status = paid`.** A message arrives with the money. ADR-0001 deletes a contribution outright when its payment fails, so a pending message is one that can vanish from the inbox after being read.
- **`visibility in (public, owner)`.** Settled by `CONTEXT.md`: "only ever the wish's owner reads one, and only while the contributor is visible to them — under `hidden` it stays unread too, since a message can name the person the setting exists to conceal." A `hidden` contribution's message never appears **at all** — not anonymised, absent. So every row here carries a real name, unlike a contributor row on a wish page.
- **`message is not null`.**

**Messages outlive their wish.** ADR-0003, and wishes are soft-deleted — a message whose wish is gone stays, naming the wish it was written for.

**Read state is one timestamp: `users.messages_seen_at`**, stamped when the owner opens the page. Anything newer counts as new, which is enough for a badge. Per-row `read_at` on `contributions` was rejected — it puts a column about the *reader* on a table about *money*, and buys mark-as-unread that nobody asked for. Having no read state at all was rejected too: nothing would ever tell an owner a message had arrived.

The badge count must ride on every page as a shared Inertia prop, which is a cost this map has not yet priced.

### `/dashboard/withdrawals`

The balance figures (available and held), the bank-details form, the request form, and the history of requests with their states.

- **No itemised statement.** Removes stories 8–10 from the balance spec. The owner sees the figures, not the contributions behind them.
- **Bank details live here**, not on the account screen. Sheba and account-holder name exist only to serve a withdrawal, so they sit next to the thing that needs them — and an owner with none saved sees the form in place of the request button, which is the balance spec's story 20 exactly.

### The accepted risk

**No password confirmation anywhere.** Not on the bank details, not on the withdrawal request.

`user/confirm-password` routes exist but there is no `ConfirmPassword.vue` and no `Fortify::confirmPasswordView()` binding, so anything gated behind `password.confirm` today would hit an unbound view and error. `.scratch/auth-pages/issues/14-confirm-password-page.md` is that work — `ready-for-agent`, blocked inside its own effort, and with no consumer at all since its only stated trigger (passkey confirmation) was disabled by issue 19.

Gating the Sheba was recommended and declined, to keep this effort free of that dependency. **The consequence is that an open session can change the bank account and then withdraw to it.** This is a decision, not an oversight, and belongs in the spec's Further Notes rather than in a future ticket.

### How far the map runs

Decision tickets, then one ticket that assembles the spec, then build tickets added afterwards — the way `auth-pages` and `wish-detail` both ran.

## Amendment

[How the profile form is tested](09-how-the-profile-form-is-tested.md) changed the route policy after this ticket closed: **`/dashboard` is the one exception to `verified`**, running on `auth` alone, so an unverified user reaches the account screen from the header and can correct a mistyped address there. `/dashboard/messages` and `/dashboard/withdrawals` keep `auth` + `verified`.

Consequences for this ticket: the account-menu row اطلاعات من is **enabled for every signed-in user** — only پیام‌ها and برداشت‌ها stay disabled behind the amber strip — and the email form on `auth/VerifyEmail.vue` is now a **kept convenience rather than the only door**. It was kept deliberately, with the duplication understood.
