# 05 — Contribution history outlives its participants

**What to build:** A contributor's history that survives the disappearance of the wish they gave to, or of their own account. Today deleting either destroys the contributions outright: a wish owner deleting a wish would wipe every contribution made to it, and a departing user's contributions would vanish from wishes that had already received the money, silently shrinking totals that people had seen.

Instead, deleting hides the party rather than erasing it. A contributor viewing their history still sees the contribution, with the wish shown as deleted rather than detailed — the UI work itself is a later phase, but the data must support it. Purging for real stays possible as a deliberate, separate act (see ADR-0003).

**The wish half is built.** `.scratch/profile/issues/04` needed it to ship a delete control, so `wishes` now has `deleted_at`, `Wish` uses `SoftDeletes`, and `tests/Feature/DeleteWishTest.php` covers a deleted wish keeping its contributions and their derived money, along with the force-delete cascade. What is left here is the **user** half, plus the two things about a wish that ticket had no reason to touch: reading a contribution's deleted wish back through the relation, and constraining `wishes.user_id`.

**Blocked by:** 02 — Contribution test data

**Status:** ready-for-agent

- [x] Wishes are soft-deleted; deleting one hides it from ordinary queries but keeps its contributions
- [ ] `contributions.contributor_id` is nullable and nulls on delete, so deleting a user keeps every contribution they made (ADR-0012)
- [ ] Users are **not** soft-deleted — see the amendment below; ADR-0003's user half is superseded
- [ ] A wish's received total and contributor count are unchanged by the deletion of a contributor
- [ ] An owner's balance is unchanged by the deletion of one of their contributors — deleting an account never shrinks a stranger's money
- [ ] A contribution can still reach its soft-deleted wish, so a caller can tell the wish is gone and render it as deleted — the relation does not carry `withTrashed()` yet
- [ ] A contribution with no contributor renders as coming from a deleted user, in every reader's view
- [ ] "Deleted" and «ناشناس» are distinct: a nulled contributor reads as deleted even on a row whose Visibility is `hidden`, overriding ADR-0010 for this case only
- [ ] `ContributionResource` and `MessageResource` handle an absent contributor without erroring, and distinguish it from a masked one
- [ ] A wish's owner relationship is a real foreign key — it is not constrained at all today
- [x] Force-deleting a wish removes its contributions for real
- [ ] Force-deleting a user leaves their contributions in place with a null contributor, and removes their wishes
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

## Comments

**Amended 2026-09-04**, during the `balance-and-withdrawals` grilling. The user half of this ticket changed shape: users are **not** soft-deleted after all. `contributions.contributor_id` becomes nullable and `nullOnDelete`, so a departing account leaves its contributions behind with no contributor rather than being hidden behind a `deleted_at`. See ADR-0012, which supersedes ADR-0003's user half and explains what is given up — the identity is gone permanently, not merely masked.

The trigger was a balance bug this ticket was not written to catch. Balances derive from contribution rows alone (ADR-0005), and `contributor_id` cascades today, so deleting an account destroys contributions that credited *other* people's balances. Nothing surfaces that: the wish owner's figure is simply smaller than it was, with no row left to explain it. No account-deletion flow exists yet, so it is latent rather than live — but it must be fixed before one ships.

The second half of the amendment is presentation. A nulled contributor reads as **deleted**, and that is a different word from «ناشناس», which means hidden. It wins over Visibility: a contributor who chose `hidden` and later deleted their account reads as deleted, not as anonymous. That was decided knowingly and it overrides ADR-0010 for this case — the reasoning is in ADR-0012.

The wish half of ADR-0003 is untouched. Wishes stay soft-deleted, so a wish and its contributor now disappear by two different mechanisms; expect that asymmetry rather than trying to unify it.
