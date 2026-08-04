# 04 — User detail view

**What to build:** One user, in full, on one screen — so an admin judging a report does not have to run queries. Who they are, when they joined, whether they are blocked, the wishes they own, and the contributions they have made to other people's wishes.

The relationship from a user to the wishes they own does not exist in the code yet; this ticket adds it.

**Blocked by:** 02 — Users list

**Status:** ready-for-agent

- [ ] An admin can open any user from the list and see their name, email, verified state, registration date, and blocked state
- [ ] The wishes the user owns are listed, with each wish's price and what it has received
- [ ] The contributions the user has made to other people's wishes are listed, with amount, wish, and status
- [ ] Amounts are shown in Toman, matching the rest of the app
- [ ] Block and unblock are available from the detail view as well as the list
- [ ] A user with no wishes and no contributions gets explained empty states, not blank sections
- [ ] A contribution the user made with hidden or owner-only visibility is still visible to the admin — visibility governs what users see, not what moderation sees
- [ ] A non-admin cannot reach the screen (404), and a guest gets the same 404
- [ ] A browser smoke test visits the detail view and asserts no JavaScript errors
- [ ] Pint clean, larastan clean
