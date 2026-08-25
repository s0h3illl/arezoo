# 09 — The wish page's data

**What to build:** `GET /wishes/{wish}`, named `wishes.show`, and everything server-side a reader of it may see — the route, its access scope, and the public contribution shape that keeps a masked contributor's identity out of the response entirely.

The load-bearing piece is masking. Inertia ships every prop into the `data-page` attribute of the HTML document, so hiding a name in the Vue template is not hiding it at all — it's publishing the data with a stylesheet over it. `App\Http\Resources\ContributionResource` (public, unnamespaced — admin's own resource stays the exemption by namespace, never by name) has to leave `name` and `avatar` off the row entirely when the reader may not see them, not null them out, because even a key present with a null value tells a reader the name exists. `state` resolves to `visible` when the contribution is `Public`, or `OwnerOnly` and the reader is this wish's owner; otherwise `anonymous`. `amount` and `settled_at` are on every row regardless — Visibility governs identity, never money (ADR-0010) — and no row carries a `message` key at all.

The date is two fields: `settled_at` ships the raw ISO timestamp, and `settled_label` ships Carbon's `diffForHumans()` (locale `fa`, already live at boot, nothing here needs to set it) run through one `NumberFormatter('fa', NumberFormatter::DECIMAL)` pass to fix its Latin digits. Both are private methods on `ContributionResource` — the only caller.

**Amended after building:** the reader-is-owner question is the resource's own, not the controller's. This ticket had it asked once in the controller and handed down through a sibling `ContributionCollection`, to stop two rows disagreeing; they can't disagree, because every row in a response reads the same request and the same wish. `ContributionResource` derives it privately (`$request->user()?->id === $this->wish->user_id`), the collection class was never built, and the controller eager-loads `wish` so the question costs one query per page rather than one per row.

The `{wish}` route parameter gets a global binding in `AppServiceProvider::configureRouteBindings()`, mirroring the `user`/`username` bindings already there, that 404s a wish whose owner is blocked. A soft-deleted wish already 404s for free through `Wish`'s existing `SoftDeletes`. `ProfileController`'s equivalent leak — found while charting this map — turned out to already be fixed (`Route::bind('username', ...)` already applies `excludingBlocked()`, and `tests/Feature/ProfileTest.php`'s `'a blocked user has no profile'` already passes); nothing to touch there.

**Blocked by:** nothing — every decision this ticket carries out is already resolved on [the map](../map.md).

**Status:** ready-for-agent

- [ ] `GET wishes/{wish}` → `WishController::show()`, named `wishes.show`, no middleware, placed between `wishes.store` and `wishes.update` in `routes/web.php`
- [ ] `Route::bind('wish', ...)` in `AppServiceProvider` scopes every `{wish}` parameter to a wish whose owner is not blocked (`Wish::query()->whereHas('owner', fn (Builder $q) => $q->excludingBlocked())->findOrFail($value)`)
- [ ] `App\Http\Resources\ContributionResource` — takes the model alone and derives the reader from the request; `state` is `visible`/`anonymous` per the rule above; `name`/`avatar` keys present only on `visible`; `amount`/`settled_at`/`settled_label` present on every row; no `message` key
- [ ] A code comment on `ContributionResource` marks where the third `deleted` state goes once `.scratch/contributions/issues/05` gives `User` a `trashed()` method to call — no guard against it is written now, since the method doesn't exist yet
- [ ] `WishController::show()` returns three props: `wish` (`WishResource`, aggregate satisfied via `$wish->loadSum('paidContributions as received_total', 'amount')`), `owner` (`UserResource`, sibling prop via `$wish->load('owner')`), `contributions` (`Inertia::scroll()` over `$wish->paidContributions()->with(['contributor', 'wish'])->orderByDesc('settled_at')->paginate(7)`)
- [ ] Feature test: a guest, a signed-in visitor, and the wish's owner all read `wishes.show` for an existing wish
- [ ] Feature test: a soft-deleted wish 404s; a wish owned by a blocked user 404s
- [ ] **Feature test: an `anonymous` row's JSON has no `name` or `avatar` key — not a null value — while `amount` and `settled_at` are present.** This is the assertion that stops the leak coming back.
- [ ] Feature test: an `owner`-visibility contribution is `visible` to the wish's owner and `anonymous` to everyone else, read from the same response for the same wish
- [ ] Feature test: rows arrive ordered by `settled_at` descending
- [ ] Feature test: `contributions.meta.total` counts every paid contribution regardless of visibility
- [ ] Pint clean, larastan clean

## Comments
