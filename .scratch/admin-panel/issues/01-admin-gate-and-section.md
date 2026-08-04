# 01 — Admin gate and the admin section

**What to build:** A part of the app only an admin can reach. An admin signs in, follows a link from the app itself, and lands in a Persian RTL admin shell with navigation between its screens. Anyone else may as well be looking at a page that does not exist.

This is the first controller in the application, so it also sets the conventions — controller shape, route organisation, Inertia page location, feature-test style — that every later ticket follows. Get them right here.

**Blocked by:** None — can start immediately.

**Status:** done

- [x] A user marked as an admin can reach the admin section and sees a shell with navigation to the screens later tickets add
- [x] The shell reads in Persian and RTL, in the app's own typography and colour language — not a second visual system
- [x] A signed-in user who is not an admin gets a 404 on every admin route, never a 403 — a 403 confirms the section exists
- [x] ~~A signed-out visitor hitting an admin route follows the app's normal redirect to login~~ — **amended:** a guest gets the same 404. A login redirect confirms the section exists just as loudly as a 403 does.
- [x] ~~An admin whose email is unverified is treated like any other unverified user~~ — **amended:** verification is not a gate on admin routes. Admin is granted out-of-band, so the admin check already carries the trust the verification gate was there to establish.
- [x] Nothing in the app — no form, no route, no request payload — can make a user an admin; the only way is a seeder or the database directly
- [ ] ~~A signed-in admin can reach the section from the app's own navigation without typing a URL~~ — **deferred:** `is_admin` is deliberately not shared with the frontend, so the app's own header has no way to know whether to show the link. Needs its own ticket if the entry point is still wanted.
- [x] A factory state produces an admin user, named in the style of the contribution factory's existing states
- [x] A browser smoke test visits the admin section and asserts no JavaScript errors
- [x] `php artisan migrate:fresh --seed` completes, larastan clean. Pint is clean on this change; `bootstrap/providers.php` fails repo-wide, unrelated and pre-existing.
