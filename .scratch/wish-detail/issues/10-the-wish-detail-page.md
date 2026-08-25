# 10 — The wish detail page

**What to build:** `WishDetail.vue`, and the two small changes that let a reader actually get to it from a profile card.

Layout is Variant B from the prototype ([The layout in the real stack](../issues/07-the-layout-in-the-real-stack.md)): the funding card doesn't survive losing its contribute CTA, so it dissolves into a `rounded-2xl bg-slate-50` money block inside the wish card, between the title row and the description — received amount, «از {price}», percentage, a clamped bar (the number is never clamped — `formatShare`'s own comment already argues why), and a remaining/complete meta line. The two-column grid is otherwise as drawn; the right column holds the contributor card alone. Owner-only edit and delete are the shipped `EditWishDialog`/`DeleteWishDialog`, restyled to 36px icon buttons, nothing about them rebuilt. «برگشت به پروفایل» is `setHeaderNav()`, the mechanism `Home.vue` already uses — not a new `AppLayout` slot.

The contributor card infinite-scrolls the `contributions` prop exactly like the profile grid scrolls `wishes` — a `ContributorRow.vue` per row, switching on `state`: `visible` gets the app's real-picture-or-emerald-initial avatar (`Profile.vue`'s pattern, not the design's cycling palette), name, `<time :datetime="row.settled_at">{{ row.settled_label }}</time>`, and amount; `anonymous` gets a slate «؟» avatar, «ناشناس», and the same real amount beside it — Visibility hides who, never how much. Empty state is «هنوز کسی کمک نکرده» with no CTA, since no contribute action exists on this page yet.

A buy-link row renders when `wish.purchase_link` is set — a plain external link (`target="_blank" rel="noopener"`), omitted when it isn't. No ticket in this map separately grilled this; the field already ships on `WishResource`, and `resources/js/types/wish.ts`'s own comment already names this page as where it belongs.

Reaching the page: a card's cover and title become `<Link :href="show(wish.id)">` from `@/routes/wishes`; the footer's edit/share/delete stay buttons outside that anchor. The card's share control now shares the wish it sits on rather than the profile: `useShare.ts`'s private `share(path)` is promoted to the exported `shareLink(path: string)`, `shareProfile()` is deleted (it had exactly one call site), and `WishCard.vue` calls `shareLink(show(wish.id).url)` with `aria-label="اشتراک لینک آرزو"`. The toast copy needs no change — it was already link-generic.

One backend line changes for the page to feel right: `WishController::update()` currently redirects to the profile unconditionally, which would bounce an owner off the wish page they just edited from. It becomes `redirect()->back()`; `destroy()` is untouched, since deleting ends the page it happened on regardless of where the delete was requested.

**Blocked by:** 09 — The wish page's data.

**Status:** ready-for-agent

- [ ] `resources/js/types/contributor-row.ts` exports `ContributorRow` — a discriminated union on `state: 'visible' | 'anonymous'`, mirroring `ContributionResource`; named apart from the admin panel's unrelated `Contribution` type already at `@/types`
- [ ] `WishDetail.vue`: photo `clamp(220px,32vw,320px)`, full-bleed, falling back to `wish-cover-placeholder`; «برآورده شد!» badge at ≥100%; money block as described, between title and description; buy-link row after the description
- [ ] `ContributorRow.vue` renders both states; the card head shows the «{n} مشارکت» pill from `contributions.meta.total`; `<InfiniteScroll data="contributions" items-element="#contributor-list">`, 7/page
- [ ] Empty state: «هنوز کسی کمک نکرده», no CTA, pill at «۰ مشارکت»
- [ ] Owner sees `EditWishDialog`/`DeleteWishDialog` on the page; a signed-in visitor and a guest see neither
- [ ] «برگشت به پروفایل» reaches the header via `setHeaderNav()`
- [ ] `WishCard.vue`: cover and title become `<Link :href="show(wish.id)">`; the footer's three controls stay buttons outside the anchor
- [ ] `WishCard.vue`'s share control calls `shareLink(show(wish.id).url)`, `aria-label="اشتراک لینک آرزو"`
- [ ] `useShare.ts`: `shareProfile()` deleted; `share()` promoted to the exported `shareLink(path: string)`
- [ ] `WishController::update()` redirects with `redirect()->back()`; `destroy()` is unchanged
- [ ] Existing `EditWishTest` assertions expecting a profile redirect gain `->from(route('profile', ...))`
- [ ] Feature test: editing from `wishes.show` redirects back to `wishes.show`; editing from the profile grid still redirects to the profile
- [ ] Browser test: a visitor clicks a card's cover or title on the profile grid and lands on that wish's own page
- [ ] Browser test: the wish's owner sees edit and delete controls on the wish's own page; a signed-in visitor sees neither
- [ ] No browser test drives a file upload through the reused `EditWishDialog` on this page — the Pest browser plugin still can't post multipart
- [ ] `bun run types:check` and `bun run build` clean; Pint clean, larastan clean; `php artisan migrate:fresh --seed` completes

## Comments
