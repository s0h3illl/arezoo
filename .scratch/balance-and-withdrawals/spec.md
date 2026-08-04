# Spec: Balance and withdrawals

Status: ready-for-agent

Blocked by: `.scratch/admin-panel/spec.md` — the withdrawals screen lives in the admin shell that spec builds.

## Problem Statement

Money arrives and then vanishes from view. When a contribution's payment verifies, that money belongs to the wish's owner — but the owner has nowhere to see it, no idea how much of it there is, and no way to get it into their bank account. A wishlist whose whole point is that other people help pay for something currently stops one step short of the thing actually being paid for.

Nobody on the platform side can move money out either: there is no request for an owner to make and no queue for an admin to work.

## Solution

Every owner gets a balance: everything contributions to their wishes have earned them, minus what they have already taken out. It is shown in two parts — available, which they can take now, and held, which is real money that becomes available three days after the contribution that earned it settled.

An owner saves their bank details once, then requests a withdrawal for any amount above the fee. The request is queued for an admin, who does the bank transfer by hand and marks it paid — or rejects it, which puts the money straight back. A fixed fee comes out of what the owner receives.

## User Stories

1. As an owner, I want to see how much money my wishes have earned me, so that I know whether I can buy the thing I wished for.
2. As an owner, I want my balance shown in Toman like every other amount in the app, so that I never have to convert anything.
3. As an owner, I want my balance split into what I can take now and what is still held, so that I am not surprised when I try to withdraw.
4. As an owner, I want to see one headline figure for everything I have earned and not withdrawn, so that I know the real size of what is mine.
5. As an owner with held money, I want to see when each part of it becomes available, so that I can plan rather than guess.
6. As an owner, I want held money to become available with no action from me, so that I never have to claim anything.
7. As an owner, I want to understand why money is held at all, so that the delay reads as a policy rather than a bug.
8. As an owner, I want a statement of the contributions that made up my balance, so that I can see where each amount came from.
9. As an owner, I want each entry in that statement to name the wish it came from and when it settled, so that I can reconcile it against what I saw on the wish.
10. As an owner, I want the withdrawals I have already made listed in the same place, so that the arithmetic of my balance is visible rather than asserted.
11. As an owner, I want a contribution to count towards my balance even if the wish it was for has since been deleted, so that deleting a wish never costs me money.
12. As an owner whose contributor deleted their account, I want the money they gave me to stay mine, so that someone else's decision does not shrink my balance.
13. As an owner with no contributions yet, I want a balance of zero explained clearly rather than an empty screen, so that I know nothing is broken.
14. As an owner, I want my balance visible only to me, so that nobody can see what I have earned.
15. As an owner, I want to save my bank details, so that I do not retype a 26-character Sheba every time I withdraw.
16. As an owner, I want my Sheba checked for the right shape as I save it, so that a typo is caught before it becomes a failed transfer.
17. As an owner, I want to record the account-holder name alongside the Sheba, so that the person paying me out can confirm the account is mine.
18. As an owner, I want to change my bank details later, so that switching banks does not lock my money in.
19. As an owner who changes their bank details, I want withdrawals I already requested to still show the account they were requested for, so that the record of where money went stays true.
20. As an owner with no bank details saved, I want to be asked for them before I can request a withdrawal, so that I do not create a request nobody can pay.
21. As an owner, I want to request a withdrawal for an amount I choose, so that I can take part of my balance and leave the rest.
22. As an owner, I want to request my entire available balance in one action, so that taking everything does not require mental arithmetic.
23. As an owner, I want to see the fee and exactly what will land in my bank account before I confirm, so that the amount that arrives is never a surprise.
24. As an owner, I want a request above my available balance refused with a clear Persian message, so that I understand held money is not withdrawable yet.
25. As an owner, I want a request at or below the fee refused, so that I never create a withdrawal that pays me nothing.
26. As an owner, I want held money to be invisible to the withdrawal form's arithmetic, so that the maximum offered to me is always a number I can actually request.
27. As an owner, I want my requested-but-unpaid withdrawal deducted from what I can request, so that I cannot accidentally ask for the same money twice.
28. As an owner with a request already awaiting a decision, I want a second request refused with an explanation, so that I know to wait rather than assume the form is broken.
29. As an owner, I want to see the state of my request on my balance screen, so that I know whether it is waiting, paid, or rejected.
30. As an owner whose request was rejected, I want the money back in my available balance immediately, so that a refusal costs me nothing but time.
31. As an owner whose request was rejected, I want to be able to request again, so that a rejection is not the end of the road.
32. As an owner, I want a paid withdrawal to stay in my history, so that I have a record of every payout I have received.
33. As an owner, I want to know that money in my balance can never be taken away or reversed, so that I can rely on the figure I am shown.
34. As an owner, I want two withdrawal requests submitted at the same moment to not both succeed, so that a double-click cannot overdraw me.
35. As an admin, I want a list of every withdrawal request, so that I have a queue to work through.
36. As an admin, I want requests awaiting a decision shown first and oldest first, so that nobody waits longer than they should.
37. As an admin, I want to filter withdrawals by state, so that I can review what has been paid separately from what is waiting.
38. As an admin, I want each request to show the owner, the amount, the fee, and the exact amount to transfer, so that I can do the bank transfer without calculating anything.
39. As an admin, I want the Sheba and account-holder name shown on the request itself, so that I am paying the account the owner named when they asked.
40. As an admin, I want to see when a request was made, so that I can tell how long someone has been waiting.
41. As an admin, I want to mark a withdrawal paid once I have done the bank transfer, so that the owner sees it completed and the queue clears.
42. As an admin, I want to confirm before marking a withdrawal paid, so that a misclick does not tell someone they were paid when they were not.
43. As an admin, I want to reject a withdrawal, so that a request I will not pay does not sit in the queue forever and the owner gets their money back.
44. As an admin, I want to see when a withdrawal was paid or rejected, so that the history of a payout is complete.
45. As an admin, I want a paid or rejected withdrawal to be final, so that a decision cannot be quietly reversed later.
46. As an admin, I want to see a user's balance on their detail view, so that I can judge a request in the context of what they have earned.
47. As an owner of the system, I want the fee and hold period configurable, so that changing them does not require a code change.
48. As an owner of the system, I want money that has never been held long enough to be unreachable by any route, so that the hold cannot be bypassed.

## Implementation Decisions

- A balance is derived on read, not stored: available is the sum of paid contributions to the user's wishes that settled more than the hold period ago, minus every withdrawal in the requested or paid state; held is the paid contributions younger than that. There is no balance column and no ledger table. See ADR-0005.
- A contribution gains a settlement timestamp, set at the moment its payment verifies. The existing settlement path already moves a contribution and its payment together in one transaction; the timestamp joins that write. `created_at` cannot serve, since it records when the gateway attempt began.
- The hold is three days, interpreted as 72 hours from settlement — no calendar-day or timezone rules. Maturity is per contribution, so a balance becomes available continuously. See ADR-0006.
- A new project configuration file holds the hold period and the withdrawal fee. The fee is a fixed 12,500 Toman.
- The fee is read live from configuration at the moment a withdrawal is paid; it is not stored on the withdrawal. Consequence, accepted deliberately: raising the fee re-prices requests already in the queue, and can leave an open request whose amount no longer exceeds the fee. The admin is expected to notice this when working the queue; nothing in the code guards against it.
- The fee comes out of the payout. A request for 100,000 Toman reduces the balance by 100,000 and produces a bank transfer of 100,000 minus the fee. The requested amount is therefore the only number validated against the available balance, and "withdraw everything" is always expressible.
- The minimum request is strictly greater than the fee — at exactly the fee the payout would be zero. There is no separate configured minimum.
- A withdrawal has three states: awaiting a decision, paid, rejected. There is no cancel — an owner cannot withdraw their own request — and paid and rejected are both terminal.
- Only one withdrawal may be awaiting a decision per user at a time. A second request is refused with an explanatory message rather than queued.
- A withdrawal awaiting a decision reserves its amount by counting against the balance exactly as a paid one does. Rejection releases it by the same arithmetic, with nothing to undo.
- Marking a withdrawal paid records the state and the time only. No bank reference is captured.
- Bank details — Sheba and account-holder name — are stored on the user, and copied onto each withdrawal at request time. A paid withdrawal therefore records the account it was actually requested for, and a later profile edit cannot rewrite it. Same instinct as ADR-0003.
- Sheba is validated for shape on save (the `IR` prefix and the correct digit length), not for existence. There is no bank-account verification service in play.
- Requesting a withdrawal re-checks the available balance inside the same transaction that creates it, under a lock on the requesting user, so two simultaneous requests cannot both pass the check.
- A settled contribution is never reversed and a balance can never be negative. There is no admin action anywhere that reduces a balance other than a withdrawal. See ADR-0004 and ADR-0005.
- Balances and withdrawal requests are owner-private; the only other party who sees them is an admin, through the admin panel.
- The withdrawals screen and the balance figure on a user's admin detail view are additions to the admin shell built by the admin-panel spec, in the same style and behind the same gates.

## Constraints recorded here, built elsewhere

These follow from decisions taken for this feature but have nowhere to attach yet. They are rules the named efforts must honour, not work this spec performs.

- **Owners may not contribute to their own wishes** (ADR-0007). No contribution-creation endpoint exists yet; the rule belongs to whichever effort builds it, and it must be enforced there before that endpoint ships. Without it, this feature completes a card → balance → bank cash-out route.
- **An account cannot be deleted while its balance is non-zero or a withdrawal is awaiting a decision.** There is no account-deletion flow and users are not soft-deletable yet (`.scratch/contributions/issues/05`). Because the hold is bounded, this block is temporary by construction — at most three days.

## Testing Decisions

- A good test asserts external behaviour at the HTTP boundary: what the balance screen reports, whether a withdrawal request is accepted or refused and why, what state the database is in afterwards, and what the owner can request next. It never calls the balance calculation directly.
- One seam: Pest feature tests over the owner-facing HTTP routes (balance screen, bank details, withdrawal request) and the admin withdrawal routes (list, mark paid, reject). The derivation is deliberately not exposed as a second tested surface, so its shape stays free to change.
- Prior art: the feature tests established by the admin-panel spec, and the repo's existing Pest style — `RefreshDatabase`, Inertia component assertions on GET, redirect and database-state assertions on POST.
- The hold boundary is asserted through behaviour, using time travel to place a contribution's settlement either side of 72 hours: money just inside the boundary is refused by the withdrawal form, money just outside it is accepted. Both sides of the boundary get a test.
- Factory states are added for withdrawals in each of the three states, and for a contribution that settled at a chosen time, following the naming style of the contribution factory's existing `paid()` and `pending()` states.
- The arithmetic gets explicit tests for the cases that make derived balances go wrong: a pending contribution not counting, a contribution to someone else's wish not counting, a rejected withdrawal releasing its reserve, a request exactly at the fee being refused, and a request one Toman above the fee being accepted.
- Concurrency gets one test that two requests for the same available balance cannot both succeed.
- Access control is tested as a matter of course: one owner cannot see another's balance, statement, or withdrawals, and a non-admin cannot reach the withdrawals queue.
- Browser smoke tests cover the balance screen and the withdrawal form for JavaScript errors, at smoke level, matching the pattern already used for the auth pages.

## Out of Scope

- Any automated payout. Every transfer is done by a human in their bank's own interface; the app records the decision, not the transfer.
- Refunds, reversals, adjustments, and promotional credit. There is no mechanism to reduce a balance other than a withdrawal, by design.
- Topping up a balance, contributing out of a balance, or transferring between users. The gateway remains the only way money enters.
- Contribution creation and gateway integration. This spec consumes contributions that settle; it does not make them settle.
- Blocking self-contribution and blocking account deletion — recorded above as constraints for other efforts.
- Any tax, invoicing, or accounting export.
- Notifying an owner by email when a withdrawal is paid or rejected; the state on the balance screen is the only notification.
- A bank-account verification or Sheba-ownership check.
- Multiple bank accounts per user.
- Per-wish accounting for the owner ("this wish earned me X") beyond what the statement's per-entry wish name gives for free.

## Further Notes

- Domain vocabulary per `CONTEXT.md`: Balance, Available, Held, Withdrawal, Contribution, Owner, Toman. Copy and test names must not drift — held money is never "pending" (pending already means the money is not real yet), and a withdrawal is never a "payout", "settlement", or "transaction".
- All amounts are integer Toman (ADR-0002). Nothing in this feature touches the gateway, so no Rial conversion arises.
- Two risks were raised during design and accepted deliberately, and should not be re-litigated in review: a 12,501 Toman request produces a 1 Toman bank transfer done by hand, and a fee increase can strand an open request at a negative payout with only the admin to catch it.
