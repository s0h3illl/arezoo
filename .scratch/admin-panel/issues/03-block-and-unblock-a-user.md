# 03 — Block and unblock a user

**What to build:** An admin can bar someone from the app, immediately, and let them back in later. A blocked user cannot sign in and is told why — in Persian, and in words that do not read like a wrong password. Blocking never touches their money: their balance, their contributions, and the totals on wishes they gave to are all exactly as they were.

Note that hiding a blocked user's content from other users is deliberately **not** in this ticket. No profile pages, wish pages, listings, or search exist yet, so there is nothing to hide and no way to prove a hiding rule works. That rule is recorded in the spec as a constraint the efforts building those pages must honour.

**Blocked by:** 02 — Users list

**Status:** ready-for-agent

- [ ] An admin can block a user from the list or their row, after a confirmation step
- [ ] Blocking records when it happened, not merely that it happened
- [ ] A blocked user attempting to sign in is refused with a Persian message distinct from the invalid-credentials message
- [ ] A blocked user's existing session stops working at the moment of blocking — they do not stay signed in until their session expires
- [ ] An admin can unblock a user, after a confirmation step, and that user can sign in again
- [ ] The list and the user's row show when they were blocked
- [ ] Blocking and unblocking leave the user's contributions, the wishes they own, and every received total untouched
- [ ] A factory state produces a blocked user
- [ ] Only an admin can block or unblock; a non-admin attempting it gets a 404
- [ ] Pint clean, larastan clean
