# 04 — Deleting a wish, without deleting the money

**What to build:** A delete control on each of the owner's own cards, and the schema change that makes it safe.

An owner may delete **any** of their wishes, in any condition, including one people have already paid towards. There is no rule about contributions blocking a delete — a profile is the owner's to curate.

That makes the schema the problem. `contributions.wish_id` is `cascadeOnDelete` today, so deleting a wish destroys every contribution to it. Those rows are not history for its own sake: balances and withdrawals are **derived** from exactly them (ADR-0005), so a cascade would silently shrink money an owner had already earned and was entitled to withdraw.

So `Wish` gains `SoftDeletes` and a `deleted_at` column. A soft delete never fires the foreign key, so the contributions survive with no other change. This is the wish half of ADR-0003 — decided long ago, never built — and it means `.scratch/contributions/issues/05` shrinks to the user half.

The foreign key itself stays `cascadeOnDelete`. It is now reachable only through an explicit `forceDelete()`, which nothing in the app calls; it remains the deliberate full-purge path ADR-0003 describes.

Deleting is owner-only and authorised on the server, not merely hidden in the UI, and is confirmed before it fires so that one misplaced click cannot remove something.

**Blocked by:** 02 — The profile page and its wish grid.

**Status:** done

- [x] `wishes` gains `deleted_at` and the model uses `SoftDeletes`, edited into the existing wishes migration
- [x] An owner sees a delete control on each of their own cards; a signed-in visitor and a guest see none
- [x] The delete route refuses a signed-in visitor and a guest, not merely hiding the control
- [x] An owner deletes a wish and it disappears from their profile
- [x] A wish with paid contributions deletes just as freely as one without
- [x] **After deleting a wish, every contribution to it still exists**
- [x] **After deleting a wish, the owner's derived total from those contributions is unchanged**
- [x] A deleted wish is absent from ordinary queries, including the profile grid and the wish count chip
- [x] The contributions foreign key still cascades on a force delete, which remains the deliberate purge path
- [x] Deleting asks for confirmation before it fires
- [x] `.scratch/contributions/issues/05` is updated to reflect that the wish half is done
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
