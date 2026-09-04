# 01 — The balance on the finance page

**Status:** ready-for-agent

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

An owner can finally see what their wishes have earned them. A new page in the dashboard, reached from a new مالی row in the account menu, showing one headline figure split into what they could take now and what is still held, and the date the next held part matures.

The balance is computed on read from the contributions themselves — there is no balance column and no ledger (ADR-0005). The hold is 72 hours from a contribution's settlement, measured per contribution, so a balance becomes available continuously rather than in batches (ADR-0006). Held money always matures with the passage of time alone; nothing can take it back.

Withdrawals do not exist yet, so nothing debits the balance in this slice and the page has no history and no request form. Ticket 02 adds both.

Two things the first draft of this spec asked for are gone and must not be reinstated here: there is no itemised statement of the contributions behind the figures, and there is no bank-details form — a Sheba belongs to a request, not to a user. See the spec's "What this rewrite changed".

Prior art: `/dashboard/messages` for a gated dashboard sub-page, and `AccountMenu.vue` for the row.

## Acceptance criteria

- [ ] A signed-in user has a finance page in the dashboard, gated `auth` + `verified` exactly as the messages page is
- [ ] A مالی row is added to `AccountMenu.vue` alongside صفحه من / اطلاعات من / پیام‌ها, and renders disabled for an unverified user the way پیام‌ها does
- [ ] An owner sees their total balance, and it equals available plus held
- [ ] Available counts only paid contributions to wishes they own that settled more than the hold period ago
- [ ] Held counts paid contributions to their wishes that settled within the hold period
- [ ] Contributions that have not settled count towards neither figure
- [ ] Contributions to someone else's wish never count towards either figure
- [ ] The held figure is accompanied by the single earliest date on which any held money becomes available — one "next release" line, not a per-contribution breakdown
- [ ] An owner with nothing held sees no release line rather than an empty one
- [ ] Money either side of the boundary is classified correctly — both sides get a test, using time travel rather than fixed dates
- [ ] The hold period lives in `config/withdrawals.php` as `hold_hours`, set to 72, not in the code
- [ ] A contribution counts towards the balance even if the wish it was made to has since been deleted
- [ ] Visibility never affects either figure — a hidden contribution's money still counts (ADR-0010)
- [ ] All figures are integer Toman, formatted as the rest of the app formats money (ADR-0002)
- [ ] An owner with no contributions sees an explained zero, not an empty screen
- [ ] One user cannot see another user's balance by any route
- [ ] A guest is redirected to login
- [ ] Feature tests assert the figures through the HTTP boundary; the derivation is never called directly
- [ ] A browser smoke test visits the page and asserts no JavaScript errors
- [ ] Pint clean, larastan clean

## Blocked by

None — can start immediately. `contributions.settled_at` and `ContributionFactory::settledAt()` already exist, shipped with `.scratch/admin-panel/issues/06`.
