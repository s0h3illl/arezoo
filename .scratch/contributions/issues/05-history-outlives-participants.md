# 05 — Contribution history outlives its participants

**What to build:** A contributor's history that survives the disappearance of the wish they gave to, or of their own account. Today deleting either destroys the contributions outright: a wish owner deleting a wish would wipe every contribution made to it, and a departing user's contributions would vanish from wishes that had already received the money, silently shrinking totals that people had seen.

**The wish half is built**, per `.scratch/profile/issues/04`: `wishes` has `deleted_at`, `Wish` uses `SoftDeletes`, and `tests/Feature/DeleteWishTest.php` covers a deleted wish keeping its contributions. **The user half is built separately**, per `.scratch/contributions/issues/06` — users are not soft-deleted; `contributions.contributor_id` is instead nullable and `nullOnDelete`, and a nulled contributor renders as deleted (ADR-0012). Do not redo that work here.

What is left in this ticket is narrower than its title now suggests: the two things about a wish and a user that ticket 06 had no reason to touch — reading a contribution's soft-deleted wish back through the relation, constraining `wishes.user_id`, and deciding what happens to a user's own wishes when their account is deleted.

**Blocked by:** 02 — Contribution test data (done); 06 — A deleted contributor is nulled (the contributor half of this ticket's original scope; do not duplicate it)

**Status:** done

- [x] Wishes are soft-deleted; deleting one hides it from ordinary queries but keeps its contributions
- [x] A contribution can still reach its soft-deleted wish, so a caller can tell the wish is gone and render it as deleted — the relation does not carry `withTrashed()` yet
- [x] A wish's owner relationship is a real foreign key — it is not constrained at all today
- [x] Force-deleting a wish removes its contributions for real
- [x] Deleting a user removes their wishes (there is no account-deletion flow yet, so this is schema-level cascade behaviour, not a built endpoint)
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

## Comments

**Amended 2026-09-04**, during the `balance-and-withdrawals` grilling. The user half of this ticket changed shape: users are **not** soft-deleted after all. `contributions.contributor_id` becomes nullable and `nullOnDelete`, so a departing account leaves its contributions behind with no contributor rather than being hidden behind a `deleted_at`. See ADR-0012, which supersedes ADR-0003's user half and explains what is given up — the identity is gone permanently, not merely masked.

The trigger was a balance bug this ticket was not written to catch. Balances derive from contribution rows alone (ADR-0005), and `contributor_id` cascades today, so deleting an account destroys contributions that credited *other* people's balances. Nothing surfaces that: the wish owner's figure is simply smaller than it was, with no row left to explain it. No account-deletion flow exists yet, so it is latent rather than live — but it must be fixed before one ships.

The second half of the amendment is presentation. A nulled contributor reads as **deleted**, and that is a different word from «ناشناس», which means hidden. It wins over Visibility: a contributor who chose `hidden` and later deleted their account reads as deleted, not as anonymous. That was decided knowingly and it overrides ADR-0010 for this case — the reasoning is in ADR-0012.

The wish half of ADR-0003 is untouched. Wishes stay soft-deleted, so a wish and its contributor now disappear by two different mechanisms; expect that asymmetry rather than trying to unify it.

**Split 2026-09-05.** The amendment above described the work but left it inside this ticket, bundled with two unrelated items (`wishes.user_id`'s FK, and cascading a user's own wishes on deletion). At the user's request, the `contributor_id` nulling and its rendering moved to its own ticket, `.scratch/contributions/issues/06-a-deleted-contributor-is-nulled.md`, so it can be built first and independently. This ticket keeps everything else the amendment did not touch.

**Built 2026-09-09.** Two of the three remaining items were narrower than the ticket read.

- `Contribution::wish()` already carried `withTrashed()`, committed alongside the wish half; the inbox and the admin list already depended on it. What was missing was a test naming the relation itself rather than the two pages that happen to use it, so `tests/Feature/DeleteWishTest.php` now pins that a contribution reaches its trashed wish, reads its title, and can tell it is trashed. Removing `withTrashed()` fails four tests, so the pin is real.

  The rendering half was missing, though, and the checkbox asks for it in as many words — a caller could reach the trashed wish but could not *tell*, because the payload sent a title and nothing else. Both resources that carry a wish now send `deleted` beside its title, and both screens that show one mark it: the inbox's «برای «…»» line and the admin list's title cell. That is what ADR-0003 means by surfacing it as deleted rather than showing its details.
- `wishes.user_id` gained `constrained()->cascadeOnDelete()`, edited into the existing migration the way the wish half's `deleted_at` was.

The cascade has a consequence the ticket does not spell out, and it is worth knowing before an account-deletion flow ships. A database cascade is a real delete, so it fires `contributions.wish_id`'s own cascade underneath it: deleting a user destroys the contributions *other people made towards that user's wishes*, rows a soft-deleted wish was written to protect. No money moves that anyone else can see — those rows only ever credited the departing owner's own balance (ADR-0005) — but the giving history goes, which is the asymmetric opposite of what ticket 06 built for the contributor half. `tests/Feature/DeleteOwnerTest.php` pins the behaviour as built rather than leaving it to be discovered, including the crossing case: a user who both owns wishes and gave to somebody else's loses the former and leaves the latter behind with a null contributor.

**This contradicts `CONTEXT.md` and needs a decision before an account-deletion flow ships.** Raised is defined there as the lifetime sum of every landed contribution, and "It only grows" — but `DashboardSnapshot` derives it as `Contribution::paid()->sum('amount')` over exactly the rows this cascade destroys, so deleting an owner makes the platform's lifetime figure go down. ADR-0003 is bent in the same place: it reserves real deletion of contributions for a deliberate `forceDelete()` purge, and an account deletion is not that. ADR-0012 is *not* the conflict a first reading suggests — the cascade it rejects outright is `contributions.contributor_id`, a different foreign key, and its reasoning (never shrink a stranger's money) still holds here, since the rows lost only ever credited the departing owner's own balance.

The three ways out, none of them this ticket's to choose: `nullOnDelete` on `wishes.user_id`, which needs a meaning for an ownerless wish; soft-deleting `User` after all, which ADR-0012 weighed and rejected for its machinery; or accepting that Raised is a sum over live owners and rewording the glossary. There is still no account-deletion flow, so all of this is schema-level and latent.
