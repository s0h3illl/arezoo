# 04 — Admin withdrawals queue

**What to build:** The queue an admin works through. Each request shows who is owed what, the exact amount to transfer, and the account to transfer it to — everything needed to do the bank transfer without calculating anything. The admin does the transfer in their bank's own interface, comes back, and marks it paid. Or rejects it, which puts the money straight back into the owner's available balance.

Paid and rejected are both final. Marking paid records the state and the time only — no bank reference is captured.

The fee is read from configuration at the moment of payment, not stored on the request. A known and accepted consequence: raising the fee re-prices requests already in the queue, and can leave an open request whose amount no longer exceeds the fee. Nothing guards against this; the admin is expected to notice it. Do not add a guard — that was decided against deliberately.

**Blocked by:** 03 — Request a withdrawal; admin-panel 01 — Admin gate and the admin section

**Status:** ready-for-agent

- [ ] An admin sees every withdrawal, with those awaiting a decision first and oldest first within that
- [ ] The queue can be filtered by state, so paid and rejected can be reviewed apart from what is waiting
- [ ] Each request shows the owner, the amount, the fee, and the exact amount to transfer, all in Toman
- [ ] Each request shows the Sheba and account-holder name recorded on the request itself, not the owner's current profile
- [ ] Each request shows when it was made, so how long someone has waited is visible
- [ ] An admin can mark a request paid, after a confirmation step, and the time is recorded
- [ ] An admin can reject a request, after a confirmation step, and the time is recorded
- [ ] Rejecting returns the amount to the owner's available balance immediately, with no separate action needed
- [ ] After a rejection the owner can request again
- [ ] A paid or rejected request cannot be changed again by any route
- [ ] The owner sees the outcome — paid or rejected — on their balance screen
- [ ] A non-admin cannot reach the queue or act on a request (404), and a guest is redirected to login
- [ ] A browser smoke test visits the queue and asserts no JavaScript errors
- [ ] Pint clean, larastan clean
