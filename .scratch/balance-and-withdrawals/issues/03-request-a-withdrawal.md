# 03 — Request a withdrawal

**What to build:** An owner asks for their money. They choose an amount, see exactly what will land in their bank account after the fee, and confirm. The request waits for an admin; meanwhile it counts against their balance so the same money cannot be requested twice.

The fee is fixed, lives in configuration, and comes out of the payout: requesting 100,000 Toman reduces the balance by 100,000 and produces a transfer of 100,000 minus the fee. That way the requested amount is the only number checked against the available balance, and "take everything" is always expressible. The minimum request is strictly greater than the fee — at exactly the fee the payout would be zero.

There is no cancel. An owner cannot withdraw their own request; only an admin ends it.

**Blocked by:** 01 — Save bank details; 02 — Balance screen

**Status:** ready-for-agent

- [ ] An owner can request a withdrawal for an amount they choose
- [ ] The form shows the fee and the exact amount that will reach the bank, before confirming
- [ ] The fee lives in project configuration alongside the hold period
- [ ] An owner can request their entire available balance in one action, without doing arithmetic themselves
- [ ] A request above the available balance is refused with a Persian message explaining that held money is not withdrawable yet
- [ ] A request at or below the fee is refused; a request one Toman above the fee is accepted
- [ ] Held money is never offered as requestable — the maximum the form allows is always a number that will actually be accepted
- [ ] An owner with no bank details saved is asked for them before they can request
- [ ] The request stores the Sheba and account-holder name as they were at the moment of requesting, so later edits to the profile cannot rewrite it
- [ ] A request awaiting a decision counts against the balance exactly as a paid one would, so the same money cannot be requested twice
- [ ] A second request while one is already awaiting a decision is refused with an explanation, not queued
- [ ] Two requests submitted simultaneously cannot both succeed — the available balance is re-checked inside the same transaction that creates the request, under a lock on the requesting user
- [ ] The owner sees the state of their request on their balance screen
- [ ] Requested and paid withdrawals appear in the statement alongside the contributions that credited the balance
- [ ] One user cannot request a withdrawal against another user's balance
- [ ] A browser smoke test visits the withdrawal form and asserts no JavaScript errors
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
