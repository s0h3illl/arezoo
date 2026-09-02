# Spec: The dashboard

Status: draft — **two decisions are still open**, see [Open Decisions](#open-decisions). Not `ready-for-agent` until they land.

## Problem Statement

A signed-in user has no section of the app that is theirs. Everything Arezoo renders today is either public — a profile at `/u/{username}`, a wish page, the landing page — or staff-only, behind the admin panel. There is nowhere to change your own name, nowhere to set the avatar and bio that your public profile already renders, nowhere to read what contributors wrote you, and nowhere to take your money out. Two of those are not merely missing but broken by omission: `Profile.vue:65,85` renders an avatar and a bio that **no screen in the app can set** — they are reachable only through a factory or a seeder — and there is **no sign-out anywhere** once a user is verified, the single `logout` link in the entire frontend living on `auth/VerifyEmail.vue`, a page a verified user never sees again.

Underneath that sits a worse trap, found while charting. A user who mistypes their email at registration lands on the verify-email notice, which offers only resend and sign-out. They cannot correct the address, and they cannot start over — `username` is unique and their abandoned account still holds the name they wanted. Their account and their username are both unrecoverable, permanently, from a single typo.

`.scratch/balance-and-withdrawals/spec.md` is `ready-for-agent` and owns the withdrawal arithmetic outright, but it was written before a dashboard existed and assumed a screen that was never built. This effort decides where that screen hangs and amends the two places the specs disagree.

## Solution

Three pages under `/dashboard`, reached from a new account menu in the site header.

| URL | Screen | Middleware |
|---|---|---|
| `/dashboard` | account info — name, username, email, avatar, bio, password | `auth` |
| `/dashboard/messages` | the messages contributors left | `auth` + `verified` |
| `/dashboard/withdrawals` | balance, bank details, request form, request history | `auth` + `verified` |

**No `DashboardLayout`.** All three are ordinary `AppLayout` pages — site header, footer, toaster. The admin panel's pattern of its own layout, header and burger was rejected: the admin panel is a separate world for staff, and a user in their dashboard has not left the site.

The only chrome change is the menu itself, plus one `<ConfigProvider dir="rtl">` the menu forces into `AppLayout`. The header's bordered user icon becomes a `reka-ui` dropdown carrying صفحه من / اطلاعات من / پیام‌ها / برداشت‌ها / خروج از حساب — which is also how the app finally gets a sign-out.

Alongside it, saving says so through the mechanism the endpoint already dictates: Fortify's `status`, untouched, for the Fortify endpoints the account screen posts to — and a toast for our own controllers. Every save this map adds is a Fortify one.

**The word «Profile» is spent.** `CONTEXT.md` gives it to the public page at `/u/{username}`. This section is the **Dashboard**, and nothing in it is ever called a profile. A root `DashboardController` sits beside `App\Http\Controllers\Admin\DashboardController` the way a root `UserController` sits beside the admin one — this repo separates admin by namespace, never by name.

## User Stories

1. As a signed-in user, I want a menu in the header that reaches my own section from any page, so that my account is never something I have to find.
2. As a signed-in user, I want to sign out, so that I can leave a shared machine — which no page in the app lets me do today.
3. As a signed-in user, I want to change my name, username and bio on one form with one save button, so that editing my details is one action rather than four.
4. As a signed-in user, I want to set the avatar my public profile already renders, so that the picture beside my name is one I chose.
5. As a user who mistyped my email at registration, I want to correct it from the verify-email notice **or** from my account screen, so that a typo costs me a wasted send rather than my account and my username.
6. As a signed-in user, I want to change my password behind my current one, so that an open session cannot silently take my account.
7. As an owner, I want to read every message left on a paid contribution to my wishes, so that the notes people wrote me are not silently discarded.
8. As an owner, I want a masked contributor's message with their name withheld, so that someone who did not want their name printed can still be heard.
9. As an owner, I want the inbox to load a first page and then more as I scroll, so that a busy wish does not ship every message into the document at once.
10. As a signed-in user, I want to be told when a save worked, so that a form that changed nothing on screen is distinguishable from one that failed.
11. As an unverified user, I want the menu to tell me my email is unverified and show me the sections I cannot reach yet, so that I learn they exist rather than finding them missing.
12. As an unverified user, I want to reach my account screen anyway, so that the screen that fixes my address is not behind the verification the address is blocking.

_Stories for `/dashboard/withdrawals` are blocked — see [Open Decisions](#open-decisions)._

## Implementation Decisions

### The routes

- **Three routes on a root `DashboardController`**, per [The destination and the shape of the dashboard](issues/00-charting.md).
- **`/dashboard` carries `auth` only — the one exception, and it is load-bearing.** Per [How the profile form is tested](issues/09-how-the-profile-form-is-tested.md), which amended charting and two other tickets. An unverified user must reach the account screen, because that is the screen that corrects a mistyped address. `/dashboard/messages` and `/dashboard/withdrawals` keep `auth` + `verified`.
- **This goes further than `.scratch/auth-pages/spec.md`'s stated policy** — "required for the domain's mutating actions… never for browsing" — deliberately, for the two gated pages. That policy is also not implemented (`wishes.store` carries only `auth`); recorded, not this effort's to fix.
- **A blocked user needs no guard.** `EnsureUserIsNotBlocked` already sits in the Fortify authenticate pipeline, so they never get a session.

### The account screen

- **One form, one save button.** Name, username, email, avatar and bio all post to the existing `PUT /user/profile-information`. Password keeps Fortify's `PUT /user/password`, which already demands `current_password` and so gates itself. No new controller and no new route — per [What changing a username costs](issues/03-what-changing-a-username-costs.md).
- **Nothing is shown on submit.** No confirmation step, no dialog, no interstitial, and **no warning copy anywhere**. Arezoo does not tell a user that renaming breaks the profile links they have shared. Only *profile* links break — wish links are `show(wish.id)`, id-based, and survive a rename untouched.
- **The freed username is takeable immediately.** `CONTEXT.md:29` stands unamended: no hold period, no reservation column, no expiry, and therefore no undo. No rate limit either — a name frees only when its holder renames, which no attacker can trigger.

### The username rule already in the codebase is broken for updates

- **`validateUsernameIsFree()` counts the signed-in user's own row**, so saving the profile form without touching the username field fails today with «قبلاً استفاده شده». This is the one real defect the effort found, confirmed in the code.
- **`usernameRules()` gains an optional user to ignore**, passed into the closure to exclude that key from the existence check. `CreateNewUser` keeps calling it with no argument and does not move; `UpdateUserProfileInformation` calls it with the user being edited. The reserved-name half applies on update exactly as on creation.
- **`RESERVED_USERNAMES` gains `support`, `help` and `arezoo`** — a one-line change with no mechanism behind it.

### The email change is stock Fortify

- Per [How an email change is carried](issues/02-how-an-email-change-is-carried.md), **superseded and rewritten mid-effort**. `UpdateUserProfileInformation` keeps `email` and both its branches: the new address is written immediately, `email_verified_at` is nulled, and the standard verification notification is mailed. **No `EmailChangeController`, no signed URL, no `sha1` replay fingerprint, no new notification.** The magic-link design charting reached for bought one recoverable failure at the price of a controller, two routes and a mail.
- **The recovery path holds on a middleware fact**: `user-profile-information.update` runs on `['web', 'auth']`, not `verified`. `auth/VerifyEmail.vue` therefore carries the same form, and since `/dashboard` also dropped `verified`, the account screen is the primary door and the notice page's form is a kept convenience.
- **`auth/VerifyEmail.vue` keeps its own form and its own خروج link anyway**, duplication understood: two forms, two wirings, two error displays, kept in step.
- **Two accepted risks, stated so review does not re-litigate them.** An unproven address is written, so a typo costs the verified status until corrected. And the old address is never notified, so with no password confirmation anywhere, a hijacked session moves it in silence.

### The avatar is downscaled in the browser, not on the server

- **`gd` is confirmed absent** (`function_exists('imagecreatetruecolor')` is `false`), so nothing server-side can resize and the wish-thumbnail pattern of storing whatever arrives does not transfer. Per [How an avatar is carried](issues/01-how-an-avatar-is-carried.md).
- **A canvas pass takes the largest centred square, scales it to 512×512 and re-encodes to WebP at 0.85** — roughly 45KB. This solves two things at once: the largest avatar rendered anywhere is 96px and `ContributorRow.vue` loads seven at 40px, so storing a 2MB phone photo would mean 14MB of transfer for seven small circles; and re-encoding **strips the EXIF GPS** that would otherwise be served publicly from a page carrying the person's real name.
- **The centre crop loses no control** — both render sites already `object-cover` inside `overflow-hidden rounded-full`, so the stored file simply matches what was always displayed. A drag-and-zoom cropper was cut: new component, drag maths, untestable in the Pest browser.
- **WebP needs no fallback branch** — a browser that cannot encode it hands back a PNG, larger but valid and still accepted by the `image` rule.
- **The server validates bytes and nothing else**: `['nullable', 'image', 'max:512']`, behind a `MAX_AVATAR_KILOBYTES` constant mirroring `StoreWishRequest::MAX_THUMBNAIL_KILOBYTES`. **No `dimensions` rule** — `getimagesize()` is available without `gd`, so it *could* be used, but nothing server-side ever decodes the image, and matching the canvas exactly would couple a server rule to a frontend constant so that raising the canvas to 1024 would silently start rejecting valid uploads.
- **A new `User::avatarUrl` accessor converts the path once.** `users.avatar` has always been published raw by both `UserResource` and `ContributionResource` and bound straight to `:src`; nothing caught it because the column is null everywhere and the app has never rendered an avatar. Both resources publish `'avatar' => $this->avatarUrl`, so **the wire format and every frontend type are unchanged**. `WishResource`'s inline `Storage::url($this->thumbnail)` is left alone — one resource, one place. Overriding the `avatar` attribute itself was rejected: a model written a path that reads back a URL stops meaning one thing.
- **It posts with the rest of the form**: `POST` with `_method: 'put'` and `forceFormData`, since multipart cannot be sent as a real `PUT`; removal is a **`remove_avatar` boolean** exactly as `remove_thumbnail` is. `WishController::thumbnailChange()`'s store/replace/delete shape transfers whole. `UpdateUserProfileInformation`'s `@param array<string, string>` docblock stops being true and must widen.
- **The file posted is not the file chosen** — accepted, and worth a comment at the call site so it does not read as a bug.

### The inbox

- **Every `paid` contribution to the reader's wishes whose `message` is not null, at all three visibilities.** Per [What the inbox shows](issues/04-what-the-inbox-shows.md), which **reverses charting's visibility filter**: `hidden` rows appear, masked as «ناشناس» exactly as a contributor row masks one.
- **The argument against was put and declined, and it is not cosmetic.** A message is free text, so masking the name does not mask the body — «مامان سلام، ساراام» hands the owner the identity the setting promised to withhold. Under this decision **`hidden` means "my name is not printed", not "the owner learns nothing"**: a label-level mask, not a guarantee. It is the better trade because the alternative silently destroys what a contributor wrote, with nothing in the app telling them so. Between a weakened promise and a silently discarded message, the weakened promise is the one both parties can see.
- **Ordered `settled_at` desc, `id` desc.** Not `created_at` — that records when a gateway attempt began, `settled_at` when `markPaid()` verified it, and `settled_at` is the column the row *displays*, so sorting by `created_at` could put a row above an older-looking date.
- **`Inertia::scroll()` + `<InfiniteScroll>` at 15 per page.** The mechanism is the app's only list pattern; the number is not — 7 is sized for a card grid and a 40px contributor row, and a message row is several lines tall.
- **The message renders in full** — no clamp, no «بیشتر», no scroll container, `whitespace-pre-wrap`, the way `wishes/Show.vue:135` already renders a description. Length is defended where it is written, not where it is read.
- **The wish title is plain text and never links**, not even for a live wish. `Contribution::wish()` already carries `withTrashed()` (ADR-0003), so a trashed wish's title reads like a live one and needs **no «حذف‌شده» marker**. Accepted consequence: an owner who wants to open the wish goes to their own profile.
- **A new `MessageResource`** — `{ id, state, contributor: { name, avatar }, message, wish: { title }, amount, settled_at }`. `state` and the `contributor` block reuse `ContributionResource`'s logic verbatim; masked rows carry «ناشناس» and a null avatar as **present keys**, never absent ones. **`ContributionResource` is not touched** — `wish-detail`'s spec states "No `message` key anywhere", and adding a conditional `message` there would make the wish page's payload depend on a condition not firing.
- **No contributor `id` reaches the browser**, so a masked row cannot be traced back to a user.
- **One empty state covering both cases** — «هنوز پیامی نداری». The two-state version was declined: it costs a second count query on every load to phrase an absence.
- **«کاربر حذف‌شده» for a departed contributor** — settled as copy, blocked on `.scratch/contributions/issues/05`, and explicitly not allowed to hold this page. A third state, distinct from «ناشناس»: reusing the anonymous copy would tell the owner the contributor chose to hide when the account is simply gone.

### The account menu

- **A `reka-ui` `DropdownMenu`, variant B — the identity card.** Per [The account menu in the header](issues/06-the-account-menu-in-the-header.md), prototyped in commit `fce8acc`, now on `main`. Trigger is the user's 38px round avatar (an emerald first-letter circle as fallback), replacing today's bordered `UserIcon`. The 260px panel opens on a name / `@username` block, then the four rows with icons, a separator, then **خروج از حساب**.
- **Sign-out is `<Link :href="logout()">`, and the spec must name it explicitly.** Wayfinder's `logout()` already carries `method: 'post'`. All three candidates were probed in a real menu: `<Link>` works, `router.post()` works, and **an Inertia `<Form>` does not** — `DropdownMenuItem`'s select closes the menu and unmounts the portal before the form submits. That is the shape a reader would reach for first.
- **An unverified user sees an amber «ایمیلت هنوز تأیید نشده» strip** between the identity header and the rows. **اطلاعات من is enabled for everyone** (the amendment from [How the profile form is tested](issues/09-how-the-profile-form-is-tested.md), since `/dashboard` dropped `verified`); پیام‌ها and برداشت‌ها render disabled — slate-300, `pointer-events-none`, `DropdownMenuItem :disabled`, so keyboard navigation skips them. Not hidden: a user who cannot see the sections cannot learn they exist.
- **`AppLayout` gains one `<ConfigProvider dir="rtl">`.** `reka-ui` reads direction from the provider, **not** from the DOM `dir` attribute, and defaults to `ltr` — proven by removing it and watching `align="end"` flip the panel to the viewport's left edge, clear of its trigger. It goes in the layout rather than per-menu so the next popper-positioned primitive does not repeat the bug. Invisible until now because every existing `reka-ui` use in the app is side-agnostic.
- **No burger, and no `md:` breakpoint.** At 375px the dropdown fits beside the logo and the landing page's anchors. `AdminHeader` needs a burger only because it renders six items inline; the dropdown *is* the mobile answer.
- **`provideHeaderNav()` is unaffected** and keeps meaning the current page's own anchors. The menu carries the sections from anywhere, so a second inline nav would be the same links built twice with an active state to keep in sync.

### Saving says «saved»

- **Wherever Fortify forces a `status`, `status` is used and nothing is changed. Where the endpoint is our own code, a toast is raised.** Per [What the shell does with a server `status`](issues/10-what-the-shell-does-with-a-server-status.md) as amended.
- **Every save this map adds is a Fortify save**, checked against `route:list`: name, username, email, bio and the avatar all post to `PUT /user/profile-information`, and the password form to `PUT /user/password`. The inbox is read-only and the account menu only navigates. **So the dashboard raises no toast at all** — the toast half of the rule governs our own controllers, today only `WishController@store`, plus the client-side toasts `useShare.ts` already raises.
- **The shared `status` prop is not edited.** `HandleInertiaRequests::share()` stands, no Fortify response class is rebound, and no `lang/fa/fortify.php` is written. `AppLayout` gains nothing here — no flash listener, no `status` mapping, no layout props. Its only change in this map is the `<ConfigProvider dir="rtl">` above.
- **The three green `status` panels stay.** `auth/Login.vue`, `auth/ForgotPassword.vue` and `auth/VerifyEmail.vue` are untouched by this map, sentinel included. The email-change form added to `VerifyEmail.vue` is a separate change to the same file.
- **The account screen maps its two sentinels to Persian in the Vue file.** `profile-information-updated` and `password-updated` are bare English constants; printing either raw shows English to a Persian user. `auth/VerifyEmail.vue:34` sets the precedent, matching its sentinel inline and rendering its own copy. The sentinel is used exactly as Fortify flashes it — only the rendering is ours.
- **Two behaviours are accepted, not solved.** `status` is a shared prop, so a Back navigation can restore the panel and a repeat save re-flashes an identical string. That is how the auth pages behave today, and the dashboard now behaves the same way. Fixing it meant moving off `status`, which is out of bounds.
- **A failed save shows its inline field error and nothing else.** No error toast anywhere in this map.

## Testing Decisions

Per [How the profile form is tested](issues/09-how-the-profile-form-is-tested.md).

- **Fortify is never re-asserted.** No test covers the write, the nulled `email_verified_at`, the verification mail, or the validation of `name`/`email` — vendor code with its own suite. **`Notification::fake()` appears nowhere**, and the email branch gets no feature test.
- **Two seams, each with a job.** App-added rules are asserted at the HTTP boundary, as `RegisterTest.php` already does for the same username rules; the browser seam is reserved for real interactions. Driving a validation matrix through a browser would be ten page loads to prove what one request proves.

**Five feature tests — the update-side username matrix.** Only what differs *because a row already exists*; shape, length, charset and Persian copy stay pinned once at register.

1. The **no-op save succeeds** — the named regression, since `validateUsernameIsFree()` counts the user's own row today.
2. A **case-only self-rename** succeeds — `sara` → `Sara` stores `sara`. The ignore must compare against the row, not the literal.
3. A rename onto a name someone else holds **fails**.
4. A rename onto a reserved name **fails**. 3 and 4 look like register duplicates but catch the obvious wrong implementation: an ignore that swallows the whole uniqueness closure still passes 1, 2 and 5.
5. A **freed name is immediately takeable** — the one assertion pinning `CONTEXT.md:29`.

**A sixth feature test — the middleware fact.** An unverified user PUTs `user-profile-information.update` and succeeds, named for the fact so the failure explains itself the day someone tidies `verified` onto the route. The ticket's "one entry-point test or two?" **dissolves here**: one route, byte-identical requests, so parameterising would be two passes and zero coverage.

**No feature test for success feedback.** `status` is Fortify's own flash and Fortify is never re-asserted; what the account screen does with it is a rendering question, proven in the browser or not at all.

**No unit test on `usernameRules()`** — what it returns is a rule array and a closure; asserting its contents is asserting implementation. Both call shapes already run through a real endpoint, and the repo has no habit of unit-testing traits.

**Five browser tests**, house standard per `EditWishTest.php`: visit, fill, click, then read the database.

1. The account screen **saves** name, username and bio.
2. The **email change** reaches the row and nulls `email_verified_at`. The mail is never opened; this stops at database truth.
3. The **verify-notice form** — exists only because the duplicate form was kept; test 2 says nothing about whether the second form is wired.
4. A **validation error renders** — the only thing proving the user ever sees one, and that the form displays errors at all.
5. **Smoke on `/dashboard`** — `assertNoJavaScriptErrors` plus the Persian copy.

**The success panel is asserted on the account screen**, in the browser, like any other rendered element. There is no toast on this map to assert and no shell mapping to assert against.

**Not tested, deliberately:**

- **The avatar, in either seam.** The Pest browser parses urlencoded bodies only, so a forced `FormData` arrives empty and reads as a broken `v-model`. **The canvas downscale — the load-bearing part of that decision — has no automated coverage at all.** Recorded here rather than left for a reviewer to discover.
- **The password form** — stock Fortify on its own endpoint.
- Nothing tests `user-profile-information` today (`grep` returns no files), so no existing test is kept or deleted and the Pest no-deletion rule never arises.

`php artisan migrate:fresh --seed` completes, Pint clean, larastan clean, `bun run types:check` clean.

## Out of Scope

- **The balance arithmetic and everything under it.** The available/held derivation, the fee, the hold period, the one-open-request rule, the concurrency lock and the entire admin withdrawals queue belong to `.scratch/balance-and-withdrawals/spec.md`. This effort decides only what the owner-facing page shows and where the bank-details form sits.
- **The itemised balance statement.** Ruled out at charting: the owner sees the figures, the request form and the request history, not the contributions behind them. Removes stories 8–10 from the balance spec.
- **A dashboard overview screen.** `/dashboard` is the account-info screen, not a landing page of summary cards. Cut at charting as a fourth surface nobody asked for — every number on it would be its own decision.
- **Managing wishes from the dashboard.** Adding, editing and deleting already happen on the public profile; the menu links there («صفحه من») and changes nothing about it.
- **Replying to a message.** The inbox is read-only. There is no messaging between users anywhere in the app and this section does not start one.
- **Password confirmation on sensitive actions.** Decided against at charting, to keep this effort free of a dependency on the unbuilt `.scratch/auth-pages/issues/14-confirm-password-page.md`. See Further Notes — this is a recorded risk, not a gap to close later.
- **Enforcing the verified-email policy on `wishes.store`.** `.scratch/auth-pages/spec.md` states that publishing a wish requires verification; the route carries only `auth`. A real gap, and that effort's to close.
- **Email notifications of any kind.** No mail when a message arrives, when a withdrawal is paid, or when one is rejected. The screens are the only notification, as the balance spec already ruled.
- **Telling the contributor that the owner reads their message whatever its visibility.** Owed to `.scratch/contributions/`, recorded below, not built here.

## Further Notes

### The accepted risk: no password confirmation anywhere

Nothing in this section asks for a password before a sensitive action. **An open session can change the Sheba and then withdraw to it**, and can move the account's email address without the old address ever being told. This was decided at charting, deliberately, to keep the effort free of a dependency on the unbuilt confirm-password page. It is recorded here as a decision taken with its reason, so that review does not re-litigate it as an oversight.

### Amendments this effort owes other efforts

None of these are optional, and none should be made silently.

- **`.scratch/balance-and-withdrawals/spec.md`** — stories 8–10 (the itemised statement) are cut. Everything else stands. **Blocked**: the precise edit is the answer to [The withdrawals page](issues/07-the-withdrawals-page-against-the-balance-spec.md), which is unresolved, so the edit has **not** been made.
- **`.scratch/auth-pages/` — one page, not four.** `auth/VerifyEmail.vue` grows the email-change form. `auth/Login.vue` and `auth/ForgotPassword.vue` are untouched, and all three keep their green `status` panels.
- **`.scratch/contributions/`** — the contribute form gains `max:512` on `message`, validated before the gateway redirect, and should tell the contributor that the owner reads their message whatever its visibility. Recorded as constraints; neither is built here.
- **`CONTEXT.md:38` is rewritten, and the reversal needs an ADR** beside ADR-0010. A message is no longer narrower than identity: the owner reads every message on a paid contribution to their wish, and Visibility governs whose *name* sits above it, never whether it is read. The ADR's body is the reasoning in [What the inbox shows](issues/04-what-the-inbox-shows.md) — the leak the mask does not close, and why the decision stands anyway.

### Glossary entries this effort owes `CONTEXT.md`

- **«Dashboard»** is a new term and `CONTEXT.md` does not have it. It needs an entry, with `_Avoid_: Profile` — the public page owns that word.
- **Visibility's entry needs amending** now that the inbox is a second reader of the message rule (see above).
- **`messages_seen_at`** — whether it introduces a glossary term of its own is **blocked** on [What the unread badge costs](issues/05-what-the-unread-badge-costs.md), which decides what the stamp actually means.

### Dependencies this effort does not own

`.scratch/contributions/issues/05` owes `User` its `SoftDeletes` trait and `Contribution::contributor()` a `withTrashed()`. The inbox's «کاربر حذف‌شده» row is decided here and waits on it — the same call `wish-detail` made for its own deleted-contributor row.

## Open Decisions

**This spec is `draft`, not `ready-for-agent`, because two decisions are unresolved.** Neither is guessed at here.

1. **[The withdrawals page against the balance spec](issues/07-the-withdrawals-page-against-the-balance-spec.md)** — open, unclaimed. Decides which of the balance spec's 48 stories the page renders, what replaces the statement's job, how the three figures read, where the bank-details form sits, what the request form does, what the history rows show, and the empty states. **Its answer is also the text of the `balance-and-withdrawals` amendment**, which is why that edit is not made above. Until it lands there are no user stories and no implementation decisions for `/dashboard/withdrawals` — a third of this section.
2. **[What the unread badge costs on every page](issues/05-what-the-unread-badge-costs.md)** — marked `claimed`, possibly a stale claim. Decides the count's query and indexes, whether it is deferred, where it is shared from, that it is never computed for guests, when `messages_seen_at` is stamped and what that stamp means for a paginated inbox, and what the badge shows past a point. The menu's **dot on the avatar and count on the پیام‌ها row** are already decided; what feeds them is not.

## Build Tickets

Seven tickets cover the settled surfaces, numbered on from this map's decision tickets. Ticket 11 is listed as closed so its number is not read as missing:

| # | Ticket | Blocked by |
|---|---|---|
| 11 | [The shell says «saved»](issues/11-the-shell-says-saved.md) — **closed, won't do** | — |
| 12 | [A username can be changed](issues/12-a-username-can-be-changed.md) — **done, `d72d30f`** | — |
| 13 | [The account screen at `/dashboard`](issues/13-the-account-screen.md) | 12 ✓ |
| 14 | [An avatar can be set](issues/14-an-avatar-can-be-set.md) | 13 |
| 15 | [The email change on the verify notice](issues/15-the-email-change-on-the-verify-notice.md) | 13 |
| 16 | [A message is no longer narrower than identity](issues/16-a-message-is-no-longer-narrower-than-identity.md) | — |
| 17 | [The inbox at `/dashboard/messages`](issues/17-the-inbox.md) | 16 |
| 18 | [The account menu in the header](issues/18-the-account-menu.md) | 13, 17 |

Ticket 12 is done. Ticket 16 can start immediately, and 13 is now unblocked.

**Two more tickets are owed and not written**, blocked on the open decisions above: the **unread badge** (its presentation is settled — a dot on the avatar, a count on the row — but not its query, its deferral, or what the stamp means), and the **withdrawals page**, which also carries the `balance-and-withdrawals` amendment and adds the برداشت‌ها row to the menu. Ticket 18 is scoped to exclude both.
