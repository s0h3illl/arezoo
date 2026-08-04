# 07 — The gateway attempt behind a contribution

**What to build:** The screen that answers "I paid and nothing happened". When a gateway attempt fails, the contribution is deleted and the failed payment survives as the only record that the attempt ever happened (ADR-0001) — which means a user's failed payment is currently invisible to everyone. This makes it visible, and lets an admin match any record in the app against the gateway's own dashboard.

**Blocked by:** 06 — Contributions list

**Status:** ready-for-agent

- [ ] An admin can see the gateway attempt behind any contribution, including its transaction and reference identifiers
- [ ] An admin can see gateway attempts that failed and left no contribution behind
- [ ] Failed attempts can be found by their transaction identifier, so a user's complaint can be matched to a record
- [ ] Failed attempts show their amount in Toman and when the attempt was made
- [ ] A pending attempt — one that reached the gateway and never came back — is distinguishable from a failed one
- [ ] Gateway attempts are read-only; there is no way to edit or delete one
- [ ] A non-admin cannot reach the screen (404), and a guest is redirected to login
- [ ] A browser smoke test visits the screen and asserts no JavaScript errors
- [ ] Pint clean, larastan clean
