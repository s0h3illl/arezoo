# 05 — Contribution history outlives its participants

**What to build:** A contributor's history that survives the disappearance of the wish they gave to, or of their own account. Today deleting either destroys the contributions outright: a wish owner deleting a wish would wipe every contribution made to it, and a departing user's contributions would vanish from wishes that had already received the money, silently shrinking totals that people had seen.

Instead, deleting hides the party rather than erasing it. A contributor viewing their history still sees the contribution, with the wish shown as deleted rather than detailed — the UI work itself is a later phase, but the data must support it. Purging for real stays possible as a deliberate, separate act (see ADR-0003).

**Blocked by:** 02 — Contribution test data

**Status:** ready-for-agent

- [ ] Wishes are soft-deleted; deleting one hides it from ordinary queries but keeps its contributions
- [ ] Users are soft-deleted; deleting one hides the account but keeps the contributions they made
- [ ] A wish's received total and contributor count are unchanged by the soft deletion of a contributor
- [ ] A contribution can still reach its soft-deleted wish and its soft-deleted contributor, so a caller can tell the party is gone and render it as deleted
- [ ] A wish's owner relationship is a real foreign key — it is not constrained at all today
- [ ] Force-deleting a wish removes its contributions for real; force-deleting a user removes their wishes and contributions for real
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
