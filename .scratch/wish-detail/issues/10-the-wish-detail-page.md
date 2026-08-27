# 10 — The wish detail page

**What to build:** `WishDetail.vue`, and the two small changes that let a reader actually get to it from a profile card.

Layout is Variant B from the prototype ([The layout in the real stack](../issues/07-the-layout-in-the-real-stack.md)): the funding card doesn't survive losing its contribute CTA, so it dissolves into a `rounded-2xl bg-slate-50` money block inside the wish card, between the title row and the description — received amount, «از {price}», percentage, a clamped bar (the number is never clamped — `formatShare`'s own comment already argues why), and a remaining/complete meta line. The two-column grid is otherwise as drawn; the right column holds the contributor card alone. The design draws owner-only edit and delete buttons beside the title; they are **not** built. The wish's own page is read-only for everyone including its owner, and managing a wish stays on the profile grid. «برگشت به پروفایل» is `setHeaderNav()`, the mechanism `Home.vue` already uses — not a new `AppLayout` slot.

The contributor card infinite-scrolls the `contributions` prop exactly like the profile grid scrolls `wishes` — a `ContributorRow.vue` per row, switching on `state`: `visible` gets the app's real-picture-or-emerald-initial avatar (`Profile.vue`'s pattern, not the design's cycling palette), name, `<time :datetime="row.settled_at">{{ row.settled_label }}</time>`, and amount; `anonymous` gets a slate «؟» avatar, «ناشناس», and the same real amount beside it — Visibility hides who, never how much. Empty state is «هنوز کسی کمک نکرده» with no CTA, since no contribute action exists on this page yet.

A buy-link row renders when `wish.purchase_link` is set — a plain external link (`target="_blank" rel="noopener"`), omitted when it isn't. No ticket in this map separately grilled this; the field already ships on `WishResource`, and `resources/js/types/wish.ts`'s own comment already names this page as where it belongs.

Reaching the page: a card's cover and title become `<Link :href="show(wish.id)">` from `@/routes/wishes`; the footer's edit/share/delete stay buttons outside that anchor. The card's share control now shares the wish it sits on rather than the profile: `useShare.ts`'s private `share(path)` is promoted to the exported `shareLink(path: string)`, `shareProfile()` is deleted (it had exactly one call site), and `WishCard.vue` calls `shareLink(show(wish.id).url)` with `aria-label="اشتراک لینک آرزو"`. The toast copy needs no change — it was already link-generic.

No backend change is needed for the page: `WishController::update()` keeps redirecting to the profile unconditionally, which is right while the profile grid is the only place a wish is edited from. `destroy()` is likewise untouched.

**Blocked by:** 09 — The wish page's data.

**Status:** ready-for-agent

- [ ] `resources/js/types/contributor-row.ts` exports `ContributorRow` — a discriminated union on `state: 'visible' | 'anonymous'`, mirroring `ContributionResource`; named apart from the admin panel's unrelated `Contribution` type already at `@/types`
- [ ] `WishDetail.vue`: photo `clamp(220px,32vw,320px)`, full-bleed, falling back to `wish-cover-placeholder`; «برآورده شد!» badge at ≥100%; money block as described, between title and description; buy-link row after the description
- [ ] `ContributorRow.vue` renders both states; the card head shows the «{n} مشارکت» pill from `contributions.meta.total`; `<InfiniteScroll data="contributions" items-element="#contributor-list">`, 7/page
- [ ] Empty state: «هنوز کسی کمک نکرده», no CTA, pill at «۰ مشارکت»
- [ ] `WishDetail.vue` imports neither `EditWishDialog` nor `DeleteWishDialog`; no owner controls render for anyone
- [ ] «برگشت به پروفایل» reaches the header via `setHeaderNav()`
- [ ] `WishCard.vue`: cover and title become `<Link :href="show(wish.id)">`; the footer's three controls stay buttons outside the anchor
- [ ] `WishCard.vue`'s share control calls `shareLink(show(wish.id).url)`, `aria-label="اشتراک لینک آرزو"`
- [ ] `useShare.ts`: `shareProfile()` deleted; `share()` promoted to the exported `shareLink(path: string)`
- [ ] `WishController` is unchanged by this issue — `update()` keeps its profile redirect, `destroy()` too
- [ ] Browser test: a visitor clicks a card's cover or title on the profile grid and lands on that wish's own page
- [ ] Browser test: owner, signed-in visitor and guest all see the wish's own page with no edit or delete control
- [ ] No browser test drives a file upload on this page — the Pest browser plugin still can't post multipart
- [ ] `bun run types:check` and `bun run build` clean; Pint clean, larastan clean; `php artisan migrate:fresh --seed` completes

## Comments

### Amended after building — the page and the type both moved

Two things this ticket specifies landed under different names, in a follow-up pass:

- **`WishDetail.vue` → `resources/js/pages/wishes/Show.vue`**, rendered as
  `Inertia::render('wishes/Show', ...)`, matching the nested-page convention the admin
  panel already follows (`admin/contributions/Index.vue`, `admin/users/Show.vue`).
- **`ContributorRow` / `types/contributor-row.ts` is not what shipped.** The name
  collision the spec worked around (Further Notes) was fixed instead of routed around:
  the admin panel's shape moved to `resources/js/types/admin/contribution.ts`, beside
  its existing `admin/user.ts` and `admin/wish.ts`, and `resources/js/types/contribution.ts`
  is now the public row mirroring `App\Http\Resources\ContributionResource`. Its
  `contributor` field is `Pick<User, 'name' | 'avatar'>` rather than a hand-written
  shape, and `ContributorRow.vue` narrows it further with `Pick` at the point of use.

The row shape changed with it: `name` and `avatar` are nested under `contributor`
rather than flat on the row, and **the contributor's `id` is not published at all** —
no page links to a contributor's profile, and withholding it means a masked row cannot
be traced back to a user through the payload. `tests/Feature/ShowWishTest.php` asserts
`missing('contributor.id')` on an anonymous row alongside the existing masking
assertions. `state` is unchanged, and so is every rendering decision above.
