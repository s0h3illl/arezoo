# 05 — Contribution history outlives its participants

**What to build:** A contributor's history that survives the disappearance of the wish they gave to, or of their own account. Today deleting either destroys the contributions outright: a wish owner deleting a wish would wipe every contribution made to it, and a departing user's contributions would vanish from wishes that had already received the money, silently shrinking totals that people had seen.

Instead, deleting hides the party rather than erasing it. A contributor viewing their history still sees the contribution, with the wish shown as deleted rather than detailed — the UI work itself is a later phase, but the data must support it. Purging for real stays possible as a deliberate, separate act (see ADR-0003).

**The wish half is built.** `.scratch/profile/issues/04` needed it to ship a delete control, so `wishes` now has `deleted_at`, `Wish` uses `SoftDeletes`, and `tests/Feature/DeleteWishTest.php` covers a deleted wish keeping its contributions and their derived money, along with the force-delete cascade. What is left here is the **user** half, plus the two things about a wish that ticket had no reason to touch: reading a contribution's deleted wish back through the relation, and constraining `wishes.user_id`.

**Blocked by:** 02 — Contribution test data

**Status:** ready-for-agent

- [x] Wishes are soft-deleted; deleting one hides it from ordinary queries but keeps its contributions
- [ ] Users are soft-deleted; deleting one hides the account but keeps the contributions they made
- [ ] A wish's received total and contributor count are unchanged by the soft deletion of a contributor
- [ ] A contribution can still reach its soft-deleted wish and its soft-deleted contributor, so a caller can tell the party is gone and render it as deleted — neither relation carries `withTrashed()` yet
- [ ] A wish's owner relationship is a real foreign key — it is not constrained at all today
- [x] Force-deleting a wish removes its contributions for real
- [ ] Force-deleting a user removes their wishes and contributions for real
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
