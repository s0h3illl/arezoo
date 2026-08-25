# 05 — The server shape for the wish page

Type: grilling
Status: resolved
Blocked by: 02, 03, 04

## Question

With what the page shows and who may see it settled, decide what the server hands it.

- **The controller.** A new `WishController::show()` beside the existing store/update/destroy, or a separate invokable controller as `ProfileController` and `TermsController` are. Note that `WishController`'s three current actions are all writes behind `auth`, and this is the first public read on it.
- **The wish prop.** `WishResource` already carries every column plus `received`, and throws a `LogicException` if a caller forgot `withReceivedTotal()`. What else does this page need beside it — the owner (as `UserResource`, for the back-to-profile control and the page title), a contributor count, anything else — and does that ride on the resource or arrive as sibling props? The resource's own comment argues for one whole-model shape narrowed with `Pick` at the point of use; honour that.
- **A public contribution resource.** Only `App\Http\Resources\Admin\ContributionResource` exists, and it is the privileged shape. This page needs the unprivileged one. Read the admin resource first, then decide the public shape's fields — and note the `edit-a-wish` convention that admin is the exemption by namespace, not by name.
- **Where masking happens — the load-bearing decision.** A contributor's name and amount that the reader may not see must never reach the browser. Inertia ships props into the page source, so masking in the Vue template is not masking at all; it is publishing the data with a stylesheet over it. The narrowing of the shared user prop in `.scratch/edit-a-wish/spec.md` was done for exactly this reason and is the standard to match. Decide whether the resource masks per-reader, whether the query filters, or both — and what the masked row's JSON actually contains, since even a key present with a null value tells a reader that a name exists.
- **Queries.** A wish page reads the wish, its owner, its paid contributions and each contribution's contributor. Say what is eager-loaded and what the scopes are — `paidContributions()` and `withReceivedTotal()` both exist, and `.scratch/contributions/issues/03` added an index on wish and status together for exactly this read.
- **Wayfinder.** The frontend reaches the route through `@/routes` or `@/actions`, never a hardcoded URL. Note which, and what the card's new links import.

## Answer

**A domain change came out of this ticket and is recorded first, because three of the decisions below hang off it.** Visibility no longer governs a contribution's amount. It governs **identity alone** — who gave, never how much. An anonymous row now shows «ناشناس» beside a real, readable amount. `CONTEXT.md`'s Visibility entry was rewritten, and [What a contributor row shows](03-what-a-contributor-row-shows.md) carries an amendment retiring its withheld-`'—'`-amount decision. A message stays narrower than either: only the wish's owner ever reads one, and not under `hidden`, since a message can name the person that setting exists to conceal. Nothing on this page renders a message, so the public resource carries no `message` key at all.

That collapses the derivable-amount problem this ticket was going to have to live with. Amounts were always arithmetically recoverable — one lone masked row means its amount *is* the wish's `received` — so withholding them was theatre over a number the page publishes anyway. Now the two agree.

**The controller is `WishController::show()`**, not a new invokable. It is the first public read on a class whose other three actions are writes behind `auth`, and that is fine: every route in `routes/web.php` declares its own middleware, none is applied at the controller level, so there is nothing for a public action to fight. `Route::get('wishes/{wish}', [WishController::class, 'show'])->name('wishes.show')` — no `auth`, no `can`.

**Three props, not one.**

- `wish` — `WishResource`, unchanged. The resource's own comment argues for one whole-model shape narrowed with `Pick` at the point of use; this page honours that and adds nothing to it. It does mean the controller must satisfy the `LogicException` guard, which route-model binding cannot: the bound model arrives without the aggregate. `$wish->loadSum('paidContributions as received_total', 'amount')` sets it as a real attribute and the guard passes.
- `owner` — `UserResource`, a **sibling prop**, not nested inside `WishResource`. Nesting it would break the resource's "every column of the `wishes` table" rule and force the profile grid to pay for an owner it already is. `$wish->load('owner')`.
- `contributions` — `Inertia::scroll(...)` over a 7-per-page paginator, per [How many contributors the page loads](04-how-many-contributors-load.md).

No `contributorCount` prop: that same ticket moved the count pill onto the paginator's `meta.total`.

**The public contribution resource is `App\Http\Resources\ContributionResource`** — unnamespaced, because `Admin\ContributionResource` is the exemption *by namespace, not by name*, the `edit-a-wish` convention. The admin one stays deliberately blind to visibility; this one is the only place visibility is ever enforced.

**Masking happens in that resource, server-side, and the masked row's JSON is a discriminated union — the withheld fields are absent, not nulled:**

```
{ id, state: 'visible',   name, avatar, amount, settled_at }
{ id, state: 'anonymous',               amount, settled_at }
{ id, state: 'deleted',                 amount, settled_at }
```

`amount` and `settled_at` are unconditional on every row. `name` and `avatar` keys exist **only** on `visible` — a reader who may not see a name receives no key for one, so there is nothing in `data-page` to read with a stylesheet peeled off. `state` also carries the third row treatment [What a contributor row shows](03-what-a-contributor-row-shows.md) settled, which a nulled-name shape could not have distinguished from anonymous without a second flag anyway.

`state` resolves in this order, and **the order is the decision**:

1. Visibility first. `Public`, or `OwnerOnly` when the reader is this wish's owner → `visible`. Otherwise → `anonymous`.
2. Only a row that survived step 1 as `visible` may degrade to `deleted`.

A row that would be anonymous **stays anonymous even when the account is gone**. Letting deletion win would tell a reader that the person who chose to hide has since deleted their account — a fact about someone the setting exists to conceal. `deleted` is specified now and dormant until `.scratch/contributions/issues/05` gives `Contribution::contributor()` its `withTrashed()`; no soft-deleted user can exist before then, so nothing can hit that branch today.

**The reader-is-owner question is asked once, in the controller, and passed down** — never re-derived per row, so two rows can't disagree. Because `ContributionResource::collection()` cannot forward constructor arguments, the page uses a sibling `ContributionCollection extends ResourceCollection` constructed as `new ContributionCollection($paginator, $readerIsOwner)`, which hands the flag to each item. Laravel already resolves a same-named collection class automatically, so this is the ordinary shape, not a workaround.

This is the one place the answer is *not* allowed to be computed in Vue. `Profile.vue`'s client-side `isOwner` precedent still applies to the edit and delete controls — those are authorised server-side and the computed value only decides what is worth drawing — but masking protects data, so it is decided before the prop is built.

**Queries.** The `{wish}` binding scopes the owner with `User::excludingBlocked` and 404s a soft-deleted wish, both owned by [Who may read a wish page](02-who-may-read-a-wish-page.md). Then: `$wish->load('owner')` and `$wish->loadSum('paidContributions as received_total', 'amount')` for the two scalar props, and `$wish->paidContributions()->with('contributor')->orderByDesc('settled_at')->paginate(7)` for the list — `with('contributor')` because a row per contributor is the N+1 this page is shaped to invite, and `orderByDesc('settled_at')` per ticket 03. The `(wish_id, status)` index from `.scratch/contributions/issues/03` is exactly this read's index.

**Wayfinder: `@/routes/wishes`, named routes, not `@/actions`** — every one of the eighteen existing imports in `resources/js/` reads from `@/routes`, including `AddWishDialog.vue` and `DeleteWishDialog.vue` on this very model. `WishCard.vue` gains `import { show } from '@/routes/wishes'` and wraps its card in a `<Link :href="show(wish.id)">`, which is how anyone reaches this page from a profile.
