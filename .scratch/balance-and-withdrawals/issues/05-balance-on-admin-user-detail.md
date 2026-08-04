# 05 — Balance on the admin user detail view

**What to build:** An admin judging a withdrawal request should be able to see it in context: what this user has earned, what is available to them, what is still held, and what they have already been paid. Today the queue shows an amount with nothing to weigh it against.

**Blocked by:** 04 — Admin withdrawals queue; admin-panel 04 — User detail view

**Status:** ready-for-agent

- [ ] A user's detail view shows their total balance, split into available and held, in Toman
- [ ] The withdrawals they have requested are listed with amount, state, and dates
- [ ] The figures shown to the admin match exactly what the owner sees on their own balance screen
- [ ] A user who has earned nothing shows an explained zero, not a blank section
- [ ] A withdrawal awaiting a decision is reflected in the figures the admin sees, as it is for the owner
- [ ] An admin can reach a user's detail view from a request in the withdrawals queue
- [ ] Nothing on the detail view can change a balance — the only route out of a balance is a withdrawal
- [ ] A non-admin cannot reach the screen (404), and a guest is redirected to login
- [ ] Pint clean, larastan clean
