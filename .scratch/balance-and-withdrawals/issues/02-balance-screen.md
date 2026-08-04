# 02 — Balance screen: available, held, and the statement

**What to build:** An owner can finally see what their wishes have earned them. One headline figure for everything that is theirs, split into what they could take now and what is still held — plus, for held money, when each part becomes available. Below it, the contributions that make up the figure, so the arithmetic is visible rather than asserted.

The balance is computed on read from the contributions themselves; there is no balance column and no ledger (ADR-0005). The hold is 72 hours from a contribution's settlement, measured per contribution, so a balance becomes available continuously rather than in batches (ADR-0006). Held money always matures with the passage of time alone — nothing can take it back.

Withdrawals do not exist yet, so nothing debits the balance in this ticket.

**Blocked by:** admin-panel 06 — Contributions list (which records the settlement time this measures from)

**Status:** ready-for-agent

- [ ] An owner sees their total balance, and it equals available plus held
- [ ] Available counts only paid contributions to wishes they own that settled more than 72 hours ago
- [ ] Held counts paid contributions to their wishes that settled within the last 72 hours
- [ ] Contributions that have not settled count towards neither figure
- [ ] Contributions to someone else's wish never count towards either figure
- [ ] Held money shows when it becomes available, per contribution
- [ ] Money either side of the 72-hour boundary is classified correctly — both sides get a test, using time travel rather than fixed dates
- [ ] The hold period lives in project configuration, not in the code
- [ ] A contribution counts towards the balance even if the wish it was made to has since been deleted
- [ ] A contribution counts towards the balance even if the contributor has since deleted their account
- [ ] Visibility never affects either figure — a hidden contribution's money still counts
- [ ] The statement lists the contributions that credited the balance, each with its wish, its amount in Toman, and when it settled
- [ ] An owner with no contributions sees an explained zero, not an empty screen
- [ ] One user cannot see another user's balance or statement by any route
- [ ] A guest is redirected to login
- [ ] A browser smoke test visits the balance screen and asserts no JavaScript errors
- [ ] Pint clean, larastan clean
