# 03 — What a contributor row shows

Type: grilling
Status: resolved
Blocked by: 01

## Question

[The charting ticket](00-charting.md) settled that every paid contribution is a row, and that a row the reader may not see in full renders as the design's anonymous variant. What a row *does* show is still open.

- **The name.** A contributor is a real `User` with both a `name` and a `username`. The design draws a name and an avatar circle bearing its first letter. Which field is the name, and does the row link to the contributor's profile — `/u/{username}` exists, and a contributor who is also a wisher has one worth visiting.
- **The avatar.** `UserResource` publishes `avatar`. The design ignores it entirely and draws a tinted initial cycling a four-tone palette. Does a contributor with a picture get their picture? If so, what is left of the palette cycle — and note that the design's cycle deliberately does not advance on anonymous rows, which is state a `v-for` does not hand you for free.
- **The date.** Settle the relative-vs-absolute question on what [How this app writes «۲ روز پیش»](01-writing-relative-time.md) turns up, including whether the `<time>` element carries the machine-readable original.
- **The message.** `contributions.message` exists, `CONTEXT.md` names it as one of the details Visibility governs, and the design draws no message anywhere. Does a row carry one? If it does, decide what a long one does in a column that is half a page wide on desktop and the full width on mobile. This is the largest piece of fog on the map.
- **A deleted contributor.** ADR-0003: the party is hidden and the UI surfaces it as deleted. No UI in the app does this yet, and `Contribution::contributor()` does not carry `withTrashed()` — `.scratch/contributions/issues/05` still owns that. Decide what the row reads as, and whether this page can ship before that ticket lands.
- **A masked row's amount.** The row's amount is withheld, but the space it occupied is not nothing. Does the row show a dash, an em-space, or drop the column for that row alone — and does the answer change when *every* row on a wish is masked?
- **Ordering.** The design says newest first. Confirm against what a wish's contributions actually sort by — `created_at` is when the gateway attempt began, `settled_at` is when the money landed, and ADR-0005's reasoning is that the two are deliberately different.

## Answer

**A row is `Wish::paidContributions()`, three fields: name, date, amount.** No message — `contributions.message` exists and Visibility governs it, but this page draws none; the design's own row payload (`{ name, amount, date, anon }`) never carried one either. Closes the map's largest fog item: there is no long-message wrapping problem to solve because there is no message.

**The name is `User::$name`**, not `$username` — the display name, matching how the rest of the app names a person. It does **not** link to the contributor's profile: the row stays plain text. Nothing in the app today links to someone else's profile from a listed name, and this page doesn't start that pattern.

**The avatar drops the design's four-tone cycling palette in favor of the app's own pattern.** `Profile.vue` already renders a real picture via reka-ui's `AvatarRoot` / `AvatarImage` / `AvatarFallback` — `user.avatar` when set, first-letter fallback (single emerald tone) otherwise — and that's precedent this page follows: a contributor with a picture gets their picture, others get the same emerald initial-letter fallback everyone else in the app gets. No palette-cycle state to track, and nothing to special-case for anonymous rows.

**Visibility masking (per `ContributionVisibility`, decided at charting): `hidden` and `owner`-for-a-non-owner render the design's anonymous variant** — slate avatar with «؟», name «ناشناس».

> **Amended by [The server shape for the wish page](05-the-server-shape.md).** This paragraph originally withheld a masked row's *amount* too, rendering it `'—'`. It no longer does: Visibility was narrowed to govern identity alone, so **an anonymous row shows its real amount** beside «ناشناس». `CONTEXT.md`'s Visibility entry was rewritten to match. The `'—'` convention is untouched elsewhere; it simply has no masked-amount case to cover here.

**A deleted contributor reads as visibly distinct from anonymous:** «کاربر حذف‌شده» over a slate avatar of its own, not the «ناشناس»/«؟» pair — "chose to hide" and "account is gone" are different facts and should read differently. Not buildable today: `Contribution::contributor()` has no `withTrashed()`, which `.scratch/contributions/issues/05` still owns. Same pattern as [Who may read a wish page](02-who-may-read-a-wish-page.md)'s soft-deleted-owner case — the behavior is decided now so that ticket's consumer doesn't re-litigate it; no code lands until the relation carries `withTrashed()`.

**Ordering is `settled_at` descending.** `created_at` marks when a gateway attempt began, not when it verified (ADR-0005); a paid-only list's natural order is by when the money actually landed, matching the same timestamp `Wish::receivedTotal()`'s definition of "paid" already keys off. The design's "optimistic insert at top" doesn't apply — this effort doesn't build the contribute flow that would produce one.
