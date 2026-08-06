# 06 — Contributions list

**What to build:** A screen that traces money through the app: every contribution, with the wish it went to, who gave it, how much, whether it landed, and **when** it landed. Read-only — no edit, no delete. A settled contribution is final (ADR-0001, ADR-0004), and nothing an admin clicks should be able to change a wish's received total or an owner's money.

This ticket also records the settlement time, which the app does not capture today. A contribution's creation timestamp marks when the gateway attempt began, not when the payment verified, so it cannot answer "when did this money land". The settlement path already moves a contribution and its payment together in one transaction; the new timestamp joins that write so a crash can never leave a contribution paid with no settlement time. The balance work depends on this timestamp.

**Blocked by:** 01 — Admin gate and the admin section

**Status:** ready-for-agent

- [x] Settling a contribution records when it settled, written in the same transaction that marks the contribution paid and its payment verified
- [x] A contribution that has not settled has no settlement time
- [x] The contribution factory can produce a contribution that settled at a chosen time, so later tickets can place money either side of a boundary
- [x] An admin sees every contribution, paginated, newest first
- [x] Each row shows the wish, the contributor, the amount in Toman, the status, and the settlement time
- [ ] ~~The list can be filtered by status, separating money that landed from money still in flight~~ — **dropped:** every contribution the app can produce is a successful payment (a failed gateway attempt deletes its contribution, ADR-0001), so `pending` is a brief in-flight state rather than a population worth filtering out. Filtering, and the query-string plumbing it needed, was removed by decision after the first pass.
- [ ] ~~The list can be narrowed to a single wish, so a received total can be reconciled against the contributions behind it~~ — **dropped** alongside the status filter, same decision.
- [ ] ~~The list can be narrowed to a single contributor~~ — **dropped** alongside the status filter, same decision.
- [x] A contribution with hidden or owner-only visibility shows its contributor to the admin
- [x] There is no way to edit or delete a contribution from this screen
- [x] A non-admin cannot reach the screen (404), and a guest gets the same 404
- [x] A browser smoke test visits the list and asserts no JavaScript errors
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

## Comments

`settled_at` landed on `contributions` by editing the original create-table migration rather than a follow-up alter migration — this app has no production data yet and resets exclusively via `migrate:fresh`, matching how `is_admin`/`is_blocked` were added to `users`. `Contribution::markPaid()` now sets it alongside `status` in the same `update()` call, inside the transaction that already wraps the payment write, so the three land or fail together. `ContributionFactory::paid()` defaults `settled_at` to `now()`; a new `settledAt(DateTimeInterface $when)` state composes on `paid()` for tests that need a specific moment (ADR-0006's held/available boundary, for later tickets).

The list is a fourth admin resource/controller pair, `ContributionResource`/`ContributionController`, following `UserResource`/`UserController`'s shape — same `$wrap = null`, same `paginate(20)`. The route is `Route::resource(...)->only(['index'])`, so no update/destroy route exists to hit — read-only is enforced at the routing layer, not by a policy check, and is tested by asserting `PUT`/`DELETE` on the URL 404.

`ContributionResource` does not branch on `visibility` at all — it always includes the contributor — which is what makes the "hidden or owner-only still shows the contributor" requirement true by construction rather than by a special case. Tested with a dataset over both `ContributionVisibility::Hidden` and `::OwnerOnly`.

Status filtering, and narrowing to a single wish or contributor, were built in the first pass (query-string filters, an entity-filter banner, a status-pill control) and then removed by decision: every contribution the app can produce already represents a successful payment, so separating "landed" from "in flight" wasn't a distinction worth a control for. If a future ticket needs to reconcile a wish's received total against its contributions, or trace a single contributor, that narrowing should be re-added then, informed by whatever UI actually links to this screen (a wish or user detail view — still deferred per 04) rather than speculatively now.
