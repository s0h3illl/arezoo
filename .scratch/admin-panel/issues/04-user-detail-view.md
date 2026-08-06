# 04 — User detail view

**What to build:** One user, in full, on one screen — so an admin judging a report does not have to run queries. Who they are, when they joined, whether they are blocked, the wishes they own, and the contributions they have made to other people's wishes.

The relationship from a user to the wishes they own does not exist in the code yet; this ticket adds it.

**Blocked by:** 02 — Users list

**Status:** ready-for-agent

- [x] An admin can open any user from the list and see their name, email, verified state, registration date, and blocked state
- [ ] The wishes the user owns are listed, with each wish's price and what it has received — **deferred**
- [ ] The contributions the user has made to other people's wishes are listed, with amount, wish, and status — **deferred**
- [ ] Amounts are shown in Toman, matching the rest of the app — **deferred** with the two lists; the screen shows no money yet
- [x] Block and unblock are available from the detail view as well as the list
- [ ] A user with no wishes and no contributions gets explained empty states, not blank sections — **deferred** with the two lists
- [ ] A contribution the user made with hidden or owner-only visibility is still visible to the admin — visibility governs what users see, not what moderation sees — **deferred** with the contributions list
- [x] A non-admin cannot reach the screen (404), and a guest gets the same 404
- [x] A browser smoke test visits the detail view and asserts no JavaScript errors
- [x] Pint clean, larastan clean

## Comments

The wishes list and the contributions list are deferred by decision, not blocked — the screen ships as identity, registration date, and blocked state alone. The user→wishes relationship the ticket opens by promising is deferred with them; nothing in the built scope needs it. The ticket stays `ready-for-agent` because the deferred half is still fully specified.

One resource serves both admin user screens: `UserResource` gained `created_at` rather than the detail view getting a resource of its own. The list leaves that field unread. 02's "a row carries nothing beyond what the list shows" became "a user carries nothing beyond what the panel serialises" to match.

The registration date is the first date the app renders anywhere. It is formatted client-side with `Intl.DateTimeFormat('fa-IR')`, which gives a Jalali date with no new dependency; the ISO original stays on the `<time datetime>` beside it.

Block and unblock from the detail view post the same request the list does and are covered by the same `update` tests. There is no browser test that clicks the button: the confirmation is `window.confirm`, which Playwright auto-dismisses and the Pest browser plugin cannot answer, so such a test would assert the cancelled path. The smoke test asserts the button is present and reads `رفع مسدودی` for a blocked user.
