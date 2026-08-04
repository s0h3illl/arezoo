# 02 — Users list

**What to build:** A screen where an admin can find any user on the platform. It lists everyone, survives the list growing, and can be searched by the two things an admin actually knows about a person — their name and their email.

**Blocked by:** 01 — Admin gate and the admin section

**Status:** done

- [x] An admin sees every user, paginated, with a stable order — newest first by id. Ordering is not covered by a test; it was judged not worth locking down.
- [x] Searching by name finds a user; searching by email finds a user; a partial match works for both. One term searches name **or** email; there is no test that a single term crosses both fields, since the two are separate paths.
- [ ] ~~Each row shows when the user registered and whether their email is verified~~ — **amended:** the registration date is dropped. A row carries name, email, verified state, and blocked state, and nothing else.
- [x] Each row shows whether the user is blocked
- [ ] ~~The list can be filtered to blocked users only~~ — **dropped:** the screen is a table and a search box. The admin sees every user — verified, unverified, blocked alike — and no status filter narrows that.
- [x] A search that matches nothing shows an explained empty state, not a blank table. There is no second empty state for a platform with no users at all: the admin reading the list is themselves a row, so an unfiltered list is never empty.
- [x] A search term survives pagination — moving to page two does not silently drop the filter
- [x] A non-admin cannot reach the screen (404), and a guest gets the same 404
- [x] A browser smoke test visits the list and asserts no JavaScript errors
- [x] Pint clean, larastan clean

## Comments

`is_blocked` (a boolean on `users`, defaulting to false) landed with this ticket rather than 03, because the list shows blocked state and cannot be built or tested without it. The `blocked()` factory state landed here for the same reason — 03 can tick that box on sight. The action that sets the flag, the sign-in refusal, and session invalidation all remain 03's work.

`0001_01_01_000000_create_users_table.php` was edited in place rather than given a follow-up migration, so any existing local database needs `php artisan migrate:fresh --seed`.
