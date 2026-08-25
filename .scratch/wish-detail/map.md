# Wish detail page

Label: wayfinder:map

## Destination

`.scratch/wish-detail/spec.md`, ready-for-agent, in the shape of `.scratch/edit-a-wish/spec.md` — Problem Statement, Solution, User Stories, Implementation Decisions, Testing Decisions, Out of Scope — with numbered issues beside it under `issues/`. Every conflict between the design and the domain resolved on paper; the building is someone else's session.

## Notes

**Domain:** Arezoo — a wishlist where people publish wishes and others help pay for them. `CONTEXT.md` holds the language; `docs/adr/` holds the decisions. ADR-0003 (history outlives the wish), ADR-0004 (contributions are uncapped), ADR-0007 (owners may not contribute to their own wishes), ADR-0009 (editing is unrestricted) and ADR-0010 (Visibility hides identity, never amount — written by this map) all bear on this page — read them before deciding anything that touches money or ownership.

**The design:** `wish-detail.dc.html` at the repo root (untracked). It is a mockup, not a requirement: it predates several of the decisions above and its cross-links point at sibling spec files that do not exist here. Where it and the domain disagree, the domain wins and the disagreement gets recorded.

**Skills to consult:** `/grilling` and `/domain-modeling` for the decision tickets; `/prototype` for the layout ticket; `/research` for the research ticket. Laravel Boost's `search-docs` before any framework-shaped decision.

**Standing preferences for this effort:**

- Plan, don't do. No route, controller or component lands from this map — the spec is the deliverable.
- Prefer what the app already does over what the design draws. `formatShare`, `WishResource`, `EditWishDialog` and `DeleteWishDialog` are all precedent, and each carries a comment saying why it is the way it is.
- Masking is a server concern. Anything a reader may not see must not reach the browser — the `edit-a-wish` effort narrowed the shared user prop for exactly this reason, and the same standard applies to contributor details.

## Decisions so far

- [The destination and the shape of the page](issues/00-charting.md) — the page is read-only plus owner controls: no contribute flow, no breadcrumb, no lists; route `GET /wishes/{wish}` named `wishes.show`; the shipped edit and delete dialogs are reused; over-funding shows «۱۴۰٪» as the rest of the app does.

- [How this app writes «۲ روز پیش»](issues/01-writing-relative-time.md) — Carbon's `diffForHumans()`, whose `fa` locale is already live at boot via Carbon's own service provider; it picks the unit and collapses days into weeks at 7. Its Latin digits are fixed by one `NumberFormatter('fa')` pass. «همین الان» is app copy and an app threshold — neither library says it. The row carries the ISO timestamp *and* the string, because `<time datetime>` still needs the original.

- [Who may read a wish page](issues/02-who-may-read-a-wish-page.md) — open to guest and authenticated reader alike; 404 for a soft-deleted wish (already free) and for a blocked owner (needs `User::excludingBlocked` applied to the binding, unused today). Folds in a pre-existing leak: `ProfileController` gets the same scope, since `/u/{username}` renders a blocked user's profile today. A soft-deleted owner will 404 the same way once user soft-deletes land (`.scratch/contributions/issues/05`). No wish-specific 404 copy — the generic `Error.vue` page already covers it.

- [What a contributor row shows](issues/03-what-a-contributor-row-shows.md) — name (`User::$name`, plain text, no profile link), date, amount; no message, closing the map's largest fog item. Avatar follows `Profile.vue`'s existing real-picture-or-initial pattern, not the design's cycling palette. Masked rows stay the charted anonymous variant (the withheld amount is since retired — see ticket 05); a deleted contributor gets its own distinct «کاربر حذف‌شده» treatment, decided now but not buildable until `.scratch/contributions/issues/05` adds `withTrashed()`. Ordered by `settled_at` descending.

- [How many contributors the page loads](issues/04-how-many-contributors-load.md) — `Inertia::scroll()` + `<InfiniteScroll>`, 7 per page, no visible "more" affordance, same pattern as the profile grid. The count pill switches from `Wish::contributorCount()` (people) to the paginator's row total, relabeled «مشارکت» instead of «نفر» so it stays true when someone gives twice — pill and list now count the same thing and can never disagree. Empty state: «هنوز کسی کمک نکرده», no CTA, pill at «۰ مشارکت».

- [The server shape for the wish page](issues/05-the-server-shape.md) — `WishController::show()` (public read on a write controller; middleware is per-route already). Three props: `wish` (`WishResource`, aggregate via `loadSum`), `owner` (`UserResource`, sibling not nested), `contributions` (`Inertia::scroll`, 7/page). Public `App\Http\Resources\ContributionResource` + `ContributionCollection` carrying one reader-is-owner flag; masked rows are a discriminated union on `state` with the withheld keys **absent, not nulled**, and anonymous beats deleted so hiding is never undone by an account going away. **Changed the domain:** Visibility now governs identity alone, never amount — `CONTEXT.md` rewritten and ticket 03 amended.

- [Sharing a wish](issues/06-sharing-a-wish.md) — the card's footer button now shares the wish (`shareLink(show(wish.id).url)`, `aria-label` «اشتراک لینک آرزو»); `shareProfile()` is **deleted**, not rehomed, since it was the app's only share control and shared the wrong thing. The detail page gets no share control of its own — accepted consequence: `navigator.share` is reachable from a card but not from a wish's own page. Toast copy was already link-generic and is untouched; the stray trailing `#` disappears because the surviving caller passes a real Wayfinder path.

- [The layout in the real stack](issues/07-the-layout-in-the-real-stack.md) — **Variant B.** The funding card does not survive losing its CTA: it dissolves into a slate-50 block inside the wish card, between title and description, and the right column holds the contributor card alone. Two-column grid, RTL side and collapse order all unchanged. Bonus find: «برگشت به پروفایل» needs no `AppLayout` change — `setHeaderNav()` is the existing mechanism. 360px left as an acceptance criterion, not a recorded verdict.

- [Write the spec](issues/08-write-the-spec.md) — [`spec.md`](spec.md) published, `ready-for-agent`, carried into [The wish page's data](issues/09-the-wish-pages-data.md) and [The wish detail page](issues/10-the-wish-detail-page.md). Every fog item this map had carried resolved into the spec (message, deleted contributor, meta tags and the contribute handoff — the last three as Out of Scope, the deleted contributor as a decided-but-blocked note). Two things no ticket had caught surfaced while writing and were settled on the spot: the design's buy-link row (`purchase_link`, already anticipated by `types/wish.ts`'s own comment) and an edit-from-this-page redirect bounce in `WishController::update()`. One thing the map had gotten wrong was corrected: `ProfileController`'s blocked-owner leak ([Who may read a wish page](issues/02-who-may-read-a-wish-page.md)) is already fixed in the codebase, so ticket 09 only adds the analogous `wish` route binding.

## Not yet specified

Empty — the way to the destination is clear. `spec.md` is published and its two implementation tickets are ready for an agent; nothing on this map remains foggy.

## Out of scope

- **Taking money.** The contribute modal, the payment gateway, the callback, verification, and the failure path all belong to a separate `.scratch/contribute/` effort. Nothing in the app creates a `Contribution` today, and this map does not change that.
- **Lists.** Wishes belong to a profile, not to named lists. The design's breadcrumb, its «از لیست X» chip, private lists and share tokens are all cut; no breadcrumb is rendered at all.
- **Re-specifying the edit modal.** §5 of the design is already shipped as `EditWishDialog.vue`. Its price-change warning and progress-delta line are a change to a shared component, wanted on the profile grid as much as here, so they are their own small effort if they are wanted at all.
- **A typed-confirmation delete with a refund warning.** The design's §2 asks for one; ADR-0003 means deleting refunds nothing and destroys no contribution. `DeleteWishDialog` already says the true thing.
- **Meta tags for a sent link.** Surfaced while [writing the spec](issues/08-write-the-spec.md): nothing in the app sets an OG title, description or image on any page today, so this is a platform-wide gap rather than one specific to this page, and belongs to its own pass.
- **Refreshing a stale relative-time label.** Also surfaced while writing the spec: a server-written string goes stale on a page left open, and research established that nothing in the app polls or re-renders on a timer today. This page doesn't start that pattern.
