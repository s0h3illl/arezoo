# 02 — Who may read a wish page

Type: grilling
Status: resolved
Blocked by: none

## Question

A wish gets its own address for the first time, which makes it reachable without going through the profile that used to be its only door. What does that door refuse?

- **A soft-deleted wish.** `Wish` uses `SoftDeletes`, so implicit binding excludes a trashed one and the route 404s by default. Is that right? A contributor holding a link to something they paid towards gets a bare 404 — ADR-0003 says history outlives the wish, and a contributor's own history is meant to show the wish "as deleted rather than detailed". Does that stance reach a public URL, or only the contributor's own list?
- **A blocked owner.** `CONTEXT.md` says nothing of a blocked user's "is reachable by anyone else". Establish what `/u/{username}` actually does for a blocked user today — the route binds a plain `User` with no scope in sight — and decide whether the wish page inherits that behaviour or whether both need fixing. If the profile leaks today, is fixing it this effort's job or a bug of its own?
- **A guest.** Profiles are public and need no account. Confirm the wish page is the same, and that nothing on it changes for a signed-out reader beyond the owner controls it was never going to show them.
- **A wish whose owner is soft-deleted.** `.scratch/contributions/issues/05` still owes user soft-deletes. Decide what this page does when that lands, or record it as depending on work not yet done.
- **The 404 itself.** `resources/js/pages/Error.vue` exists. Check what it renders and whether a missing wish deserves anything more specific than the generic page.

## Answer

**Rule:** `GET /wishes/{wish}` (`wishes.show`) is open to every reader, authenticated or guest, as long as the wish exists, is not soft-deleted, and its owner is not blocked. Nothing on the page differs for a guest versus a signed-in non-owner — both see the same read-only page; only the owner ever sees the edit/delete controls, which was already the case for the shipped dialogs.

**Two 404 triggers, one generic page:**

- **Soft-deleted wish.** `Wish` uses `SoftDeletes`; implicit route-model binding already excludes a trashed row, so this needs no code — the route 404s today. This holds for *everyone*, including a contributor who paid toward it: ADR-0003's "surfaced as deleted, not detailed" promise is scoped to the contributor's own history list (a separate, already-existing UI), not to direct navigation. A stale link to a deleted wish is a bare 404 for its contributors too.
- **Blocked owner.** Not free today — the wish route has no owner-blocked check, and `User::excludingBlocked` (`app/Models/User.php`) is unused anywhere in the app despite its own doc comment promising "not their profile, not the wishes on it." The spec must have `wishes.show`'s binding exclude wishes whose owner is blocked, 404ing the same as a soft-deleted wish.

**Pre-existing leak, pulled into this effort's scope:** `ProfileController::__invoke` binds a plain `User $user` with no scope at all, so `/u/{username}` renders a blocked user's profile today — contradicting `excludingBlocked`'s own comment and CONTEXT.md's "nothing of theirs is reachable by anyone else." Decided: fix both doors in this spec rather than splitting it into a separate bug. `ProfileController`'s binding gets `excludingBlocked` applied too, so a blocked user's profile 404s the same way their wishes now do — one `is_blocked` story instead of two.

**Owner soft-deleted (blocked on `.scratch/contributions/issues/05`, not yet buildable — `users` has no `deleted_at` column today):** decided in advance so whoever lands that ticket doesn't have to re-litigate it — a soft-deleted owner's wish page 404s, the same as a blocked owner's. "Gone as far as everyone else is concerned" (CONTEXT.md's own phrase for blocked) reads the same for a departed account. No code changes now; this is a note for that ticket's consumer.

**The 404 page itself:** no wish-specific messaging. `Error.vue` already renders one generic 404 for every resource in the app (nothing greps for a per-resource `abort(404, ...)` message anywhere in `app/Http/Controllers/`), so a missing/blocked wish gets the same "این صفحه پیدا نشد" as anything else. Consistent with existing precedent; no new copy.
