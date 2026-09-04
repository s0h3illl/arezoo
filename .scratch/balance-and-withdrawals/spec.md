# Spec: Balance and withdrawals

Status: ready-for-agent

Supersedes the first draft of this spec in full. The rewrite followed a grilling session on 2026-09-04; the decisions it reversed are listed under [What this rewrite changed](#what-this-rewrite-changed) so review does not re-litigate them.

## Problem Statement

Money arrives and then vanishes from view. When a contribution's payment verifies, that money belongs to the wish's owner — but the owner has nowhere to see it, no idea how much of it there is, and no way to get it into their bank account. A wishlist whose whole point is that other people help pay for something currently stops one step short of the thing actually being paid for.

Nobody on the platform side can move money out either: there is no request for an owner to make and no queue for an admin to work. The admin section already has a برداشت‌ها nav item pointing at `#`, waiting for the screen that does not exist.

## Solution

Every owner gets a balance: everything contributions to their wishes have earned them, minus what they have already taken out. It is shown in two parts — available, which they can take now, and held, which is real money that becomes available 72 hours after the contribution that earned it settled.

An owner opens مالی in their account menu, sees those figures and every request they have ever made, and asks for an amount. They type the destination Sheba into the request itself; the app checks its shape and its check digits, shows the fee and exactly what will reach the bank, and files the request.

An admin works a single queue. Each row carries everything needed to make the transfer by hand — who, how much, the fee, the exact amount to send, and the Sheba to send it to. The admin accepts the request, does the transfer in their bank, and marks it paid; or rejects it, which returns the money to the owner's available balance immediately. At any point they can attach a note the owner will read.

## User Stories

### Seeing the balance

1. As an owner, I want to see how much money my wishes have earned me, so that I know whether I can buy the thing I wished for.
2. As an owner, I want my balance shown in Toman like every other amount in the app, so that I never have to convert anything.
3. As an owner, I want one headline figure for everything I have earned and not withdrawn, so that I know the real size of what is mine.
4. As an owner, I want that figure split into what I can take now and what is still held, so that I am not surprised when I try to withdraw.
5. As an owner with held money, I want to see when the next part of it becomes available, so that I can plan rather than guess.
6. As an owner, I want held money to become available with no action from me, so that I never have to claim anything.
7. As an owner, I want to understand why money is held at all, so that the delay reads as a policy rather than a bug.
8. As an owner, I want to know that money in my balance can never be taken away or reversed, so that I can rely on the figure I am shown.
9. As an owner, I want a contribution to count towards my balance even if the wish it was for has since been deleted, so that deleting a wish never costs me money.
10. As an owner whose contributor deleted their account, I want the money they gave me to stay mine, so that someone else's decision does not shrink my balance.
11. As an owner, I want a hidden contribution's money to count exactly like any other, so that a contributor's privacy choice never costs me money.
12. As an owner with no contributions yet, I want a balance of zero explained clearly rather than an empty screen, so that I know nothing is broken.
13. As an owner, I want my balance visible only to me, so that nobody can see what I have earned.
14. As an owner, I want to reach all of this from a مالی item in my account menu, so that I do not have to remember a URL.

### Requesting a withdrawal

15. As an owner, I want to request a withdrawal for an amount I choose, so that I can take part of my balance and leave the rest.
16. As an owner, I want to request my entire available balance in one action, so that taking everything does not require mental arithmetic.
17. As an owner, I want to type the destination Sheba on the request itself, so that each request says exactly where its own money should go.
18. As an owner, I want my Sheba checked for its prefix, its length, and its check digits, so that a typo is caught before it becomes a transfer to a stranger.
19. As an owner, I want to see the fee and exactly what will land in my bank account before I confirm, so that the amount that arrives is never a surprise.
20. As an owner, I want the fee that applied when I asked to be the fee I am charged, so that a later change of policy cannot re-price a request I already agreed to.
21. As an owner, I want a request above my available balance refused with a clear Persian message, so that I understand held money is not withdrawable yet.
22. As an owner, I want a request at or below the fee refused, so that I never create a withdrawal that pays me nothing.
23. As an owner, I want held money to be invisible to the withdrawal form's arithmetic, so that the maximum offered to me is always a number I can actually request.
24. As an owner, I want my requested-but-unpaid withdrawal deducted from what I can request, so that I cannot accidentally ask for the same money twice.
25. As an owner with a request already open, I want a second request refused with an explanation, so that I know to wait rather than assume the form is broken.
26. As an owner, I want two withdrawal requests submitted at the same moment to not both succeed, so that a double-click cannot overdraw me.
27. As an owner, I want a request to be refused if I am not the person whose balance it draws on, so that nobody can withdraw against my money.

### Following a request

28. As an owner, I want every request I have ever made listed on the same page as my balance, so that the arithmetic of that balance is visible rather than asserted.
29. As an owner, I want each request to show its amount, its fee, what reached or will reach my bank, and the Sheba I gave, so that I can reconcile a payout against my bank statement.
30. As an owner, I want to see whether my request is waiting, accepted, paid, or rejected, so that I know whether anyone has looked at it.
31. As an owner, I want to see when I made a request and when it was decided, so that I can tell how long it took.
32. As an owner, I want to read the note an admin left on my request, so that a decision is explained rather than announced.
33. As an owner whose request was rejected, I want the money back in my available balance immediately, so that a refusal costs me nothing but time.
34. As an owner whose request was rejected, I want to be able to request again, so that a rejection is not the end of the road.
35. As an owner, I want a paid withdrawal to stay in my history forever, so that I have a record of every payout I have received.
36. As an owner who has never requested anything, I want an explained empty state rather than a blank panel, so that I know where requests will appear.

### Working the queue

37. As an admin, I want a list of every withdrawal ever requested, so that I have a queue to work through and a history to look back on.
38. As an admin, I want undecided requests shown first and oldest first among them, so that nobody waits longer than they should.
39. As an admin, I want to filter the queue by state, so that I can review what has been paid separately from what is waiting.
40. As an admin, I want every row to carry the owner, the amount, the fee, the exact amount to transfer, and the Sheba, so that I can make the transfer without calculating or navigating anything.
41. As an admin, I want to see when a request was made, so that I can tell how long someone has been waiting.
42. As an admin, I want to see that a request's owner is blocked, so that I do not pay an account I have barred from the app.
43. As an admin, I want to accept a request before I transfer, so that a request being worked is visibly distinct from one nobody has touched.
44. As an admin, I want to mark an accepted request paid once the transfer is done, so that the owner sees it completed and the queue clears.
45. As an admin, I want to reject a request either before or after accepting it, so that discovering a problem mid-way is not too late.
46. As an admin, I want to confirm before accepting, paying, or rejecting, so that a misclick does not tell someone they were paid when they were not.
47. As an admin, I want a paid or rejected request to be final, so that a decision cannot be quietly reversed later.
48. As an admin, I want rejecting to return the money to the owner without any separate action, so that a refusal cannot leave money stranded.
49. As an admin, I want to leave a note on any request at any time, so that I can tell an owner what I need from them.
50. As an admin, I want to edit that note after a request is decided, so that I can still explain a rejection I made in a hurry.
51. As an admin, I want an admin's own withdrawal to appear in the queue like anyone else's, so that staff are paid through the same process everyone else is.
52. As an admin, I want the queue to be unreachable by anyone who is not an admin, so that Shebas and payout amounts stay inside the panel.

### Keeping the money honest

53. As an owner of the system, I want the fee and the hold period configurable, so that changing them does not require a code change.
54. As an owner of the system, I want money that has never been held long enough to be unreachable by any route, so that the hold cannot be bypassed.
55. As an owner of the system, I want no route anywhere that reduces a balance other than a withdrawal, so that the contributions remain the only truth about what someone has earned.

## Implementation Decisions

### The balance

- A balance is derived on read, not stored. Available is the sum of paid contributions to the user's wishes that settled more than the hold period ago, minus every withdrawal that is not rejected; held is the paid contributions younger than that. There is no balance column and no ledger table. See ADR-0005.
- A contribution's settlement timestamp is the measurement point, not `created_at`. This already exists — `contributions.settled_at` is written inside the transaction that marks a contribution paid, and `ContributionFactory` has a `settledAt()` state.
- The hold is 72 hours from settlement, per contribution, so a balance becomes available continuously rather than in batches. No calendar-day or timezone rules. See ADR-0006.
- `config/withdrawals.php` holds `fee` and `hold_hours`. It is the app's first domain config file. The fee is 12,500 Toman; the hold is 72.
- Visibility never affects a balance. A hidden contribution's money counts exactly like a public one's (ADR-0010).
- A soft-deleted wish keeps crediting its owner's balance, because `Wish` soft-deletes and the `contributions.wish_id` cascade never fires. A deleted contributor likewise keeps crediting the wish owner, once `contributions.contributor_id` becomes `nullOnDelete` — see [Constraints recorded here, built elsewhere](#constraints-recorded-here-built-elsewhere).

### The withdrawal

- A withdrawal has four states and this transition graph:

  ```
  Requested ──accept──> Accepted ──mark paid──> Paid      (terminal)
      │                     │
      └──────reject─────────┴──────────────────> Rejected (terminal)
  ```

  `Requested`, `Accepted` and `Paid` all reserve their amount against the balance identically. Only `Rejected` releases it, and it does so by the same arithmetic that reserved it — there is nothing to undo.
- The state enum is `WithdrawalStatus::{Requested, Accepted, Paid, Rejected}`. The first case is deliberately not `Pending`: `ContributionStatus::Pending` already exists in this codebase and means the opposite — money that may never arrive — and `CONTEXT.md` reserves against that confusion under **Held**.
- Only one withdrawal may be open per user at a time, where open means `Requested` or `Accepted`. A second request is refused with an explanatory Persian message rather than queued.
- There is no cancel. An owner cannot end their own request and has no in-app way to signal that they want it stopped; every ending is an admin's. Accepted consequence: a Sheba typed wrongly onto an open request cannot be recalled by the owner, which is why the check-digit validation below is not optional.
- Requesting re-checks the available balance inside the same transaction that creates the withdrawal, under a lock on the requesting user, so two simultaneous requests cannot both pass.
- Marking a withdrawal paid or rejected records the state and the time. No bank reference is captured.

### The Sheba

- The Sheba is a field on the withdrawal, typed afresh on every request. It is **not** stored on the user, there is no saved bank-details form, and nothing is snapshotted — the Sheba is native to the request that uses it.
- No account-holder name is captured anywhere. The queue row already shows the owner's platform name, and an Iranian bank's transfer screen shows the destination account holder before the transfer is confirmed, so the admin has two names to compare without the owner typing one.
- Validation is prefix (`IR`), length, and ISO 13616 mod-97 check digits. This is the app's first entry in `app/Rules`, with its message in `lang/fa/validation.php`. Check digits are the point: they reject essentially every single-digit typo and transposition, which length alone waves through, and the owner has no way to correct a Sheba once the request is filed.
- Nothing verifies that the account exists or belongs to the requester. There is no such service in play and none is added.

### The fee

- The fee is copied onto the withdrawal when it is requested and never read from config again for that row. Config prices new requests only.
- The fee comes out of the payout. A request for 100,000 Toman reduces the balance by 100,000 and produces a bank transfer of 100,000 minus the fee, so the requested amount is the only number validated against the available balance and "withdraw everything" is always expressible.
- The minimum request is strictly greater than the fee, because at exactly the fee the payout would be zero. There is no separately configured minimum.

### The note

- `withdrawals.note` is text, defaults to `''`, and is never null. This diverges from `contributions.message`, which is nullable, and is deliberate: it follows the instinct recorded in `lang/fa/contributions.php`, where a masked value "stands in for a real one rather than being absent, so every row reads the same shape."
- Only an admin writes it. It is written through its own button on the queue row, which opens a modal containing a single textarea, and it is writable in **every** state including `Paid` and `Rejected`. Finality governs the state and the money, not the note.
- The owner reads it on their finance page. It is always optional — an unexplained rejection remains possible by design.
- It is the only channel that runs from an admin back to an owner anywhere in this feature. There are no email notifications.

### The owner's screen

- One page at `/dashboard/finance`, behind `auth` + `verified`, reached from a new مالی item in `AccountMenu.vue`. It carries three things: the balance figures, the history of every withdrawal the owner has made, and the request form.
- There is no itemised statement of the contributions behind the balance. Stories 8–10 of the first draft are cut, per `.scratch/dashboard/`.
- The held figure is accompanied by the single earliest date on which any held money becomes available — a "next release" line, not a per-contribution breakdown, since the breakdown was the statement's job.
- Bank details have no home of their own, because they are no longer stored. `/dashboard` is untouched by this effort.

### The admin's screen

- One queue at `/admin/withdrawals`, replacing the `href: '#'` placeholder already in `AdminHeader.vue`. There is no detail or show page anywhere in the section.
- Each row carries the owner's name, the requested amount, the fee, the exact amount to transfer, the Sheba, the request date, the decision date once there is one, the state, a badge when the owner is blocked, and three buttons: the state-dependent action (accept, or mark paid), reject, and note.
- Ordering is undecided requests first, oldest first within them; then decided ones, newest first. A state filter offers all four states.
- Accept, mark paid, and reject each confirm through `window.confirm` with a Persian question, matching `admin/users/Show.vue`. The note modal saves without a confirmation.
- A blocked owner changes nothing mechanically. The badge is information for the admin, who decides.
- The queue lists **every** withdrawal, including one owned by an admin. `excludingAdmins()` is a query belonging to the users list, not a rule about withdrawals.
- No row links to a user detail view. ADR-0008's route-model binding stays untouched, and the question of what a link to an admin owner would do never arises.

## Testing Decisions

- A good test asserts external behaviour at the HTTP boundary: what the finance page reports, whether a request is accepted or refused and why, what state the database is in afterwards, and what the owner can request next. It never calls the balance derivation directly — the derivation is deliberately not exposed as a second tested surface, so its shape stays free to change.
- Two seams, both already established in this repo. No third seam is introduced.

| Seam | Files | Covers |
| --- | --- | --- |
| HTTP feature tests | `tests/Feature/FinanceTest.php`, `tests/Feature/RequestWithdrawalTest.php`, `tests/Feature/Admin/WithdrawalsTest.php` | Every figure the finance page reports, acceptance and refusal of requests with their reasons, all four states and every legal and illegal transition, reserve and release arithmetic, the hold boundary, access control |
| Browser smoke | `tests/Browser/FinanceSmokeTest.php`, `tests/Browser/Admin/WithdrawalsSmokeTest.php` | JavaScript errors only, matching `tests/Browser/Admin/ContributionsSmokeTest.php` |

- Prior art: `tests/Feature/Admin/BlockUserTest.php` and `tests/Feature/Admin/ContributionsTest.php` for the panel's shape — `RefreshDatabase`, Inertia component assertions on GET, redirect and database-state assertions on POST.
- A `WithdrawalFactory` gains a state per status, following `ContributionFactory`'s `paid()` / `pending()` / `settledAt()` naming.
- The hold boundary is asserted through behaviour, with time travel placing a contribution's settlement either side of 72 hours: money just inside is refused by the request form, money just outside is accepted. Both sides get a test.
- The mod-97 rule gets **no** unit test. Known-good and known-bad Shebas go through the request form as a Pest dataset, including a transposed pair and a single-digit change that length alone would accept.
- The arithmetic gets explicit tests for the cases that make derived balances go wrong: a pending contribution not counting, a contribution to someone else's wish not counting, a hidden contribution counting, an `Accepted` withdrawal reserving exactly as a `Paid` one does, a rejected withdrawal releasing its reserve, a request exactly at the fee refused, and a request one Toman above it accepted.
- The fee snapshot gets its own test: a withdrawal requested before a config change is paid out at the fee it recorded, not the new one.
- Every illegal transition is tested for refusal — paying a `Requested` row without accepting it, deciding a `Paid` or `Rejected` row again by any route.
- Concurrency gets one test that two requests for the same available balance cannot both succeed.
- Access control is tested as a matter of course: one owner cannot see another's balance or withdrawals or request against them, a non-admin gets 404 from the queue and every action on it, and a guest is redirected to login from the finance page.
- No browser test clicks a `window.confirm` button. Playwright auto-dismisses it and the Pest browser plugin cannot answer it, so such a test would assert the cancelled path — the same known gap recorded on `.scratch/admin-panel/issues/04`. The smoke test asserts the buttons are present.

## Out of Scope

- Any automated payout. Every transfer is done by a human in their bank's own interface; the app records the decision, not the transfer.
- Refunds, reversals, adjustments, and promotional credit. There is no mechanism to reduce a balance other than a withdrawal, by design.
- Topping up a balance, contributing out of a balance, or transferring between users. The gateway remains the only way money enters.
- Contribution creation and gateway integration. This spec consumes contributions that settle; it does not make them settle.
- An itemised statement of the contributions behind the balance.
- Storing bank details, multiple bank accounts, and any bank-account or Sheba-ownership verification.
- Any owner-initiated cancellation, and any in-app way for an owner to ask for one.
- Email or any other notification. The finance page is the only place an owner learns anything.
- A history of note edits. The note is current text, not an audit trail.
- Any tax, invoicing, or accounting export.
- Balance figures on the admin user detail view. Dropped outright — see [What this rewrite changed](#what-this-rewrite-changed).

## Constraints recorded here, built elsewhere

These follow from decisions taken for this feature but have nowhere to attach yet. They are rules the named efforts must honour, not work this spec performs.

- **`contributions.contributor_id` becomes nullable and `nullOnDelete`**, and a nulled contributor renders as deleted everywhere. Decided during this spec's grilling, but it is contributions work: it belongs to `.scratch/contributions/issues/05-history-outlives-participants.md` and is recorded in ADR-0012. Until it lands, deleting a user hard-deletes the contributions they made and silently shrinks a *different* owner's balance.
- **Owners may not contribute to their own wishes** (ADR-0007). No contribution-creation endpoint exists yet; the rule belongs to whichever effort builds it and must be enforced there before that endpoint ships. Without it, this feature completes a card → balance → bank cash-out route.
- **An account cannot be deleted while its balance is non-zero or a withdrawal is open.** There is no account-deletion flow yet. Because the hold is bounded, this block is temporary by construction — at most three days.

## Further Notes

- Domain vocabulary per `CONTEXT.md`: Balance, Available, Held, Withdrawal, Note, Contribution, Owner, Toman. Copy and test names must not drift — held money is never "pending", the first state of a withdrawal is `Requested` and never "pending", and a withdrawal is never a "payout", "settlement", or "transaction".
- All amounts are integer Toman (ADR-0002). Nothing in this feature touches the gateway, so no Rial conversion arises.
- One risk was raised during design and accepted deliberately, and should not be re-litigated in review: a 12,501 Toman request produces a 1 Toman bank transfer done by hand.
- `AdminHeader.vue` already carries a برداشت‌ها item pointing at `href: '#'`. It is not a new nav entry; it is a placeholder to fill.

## What this rewrite changed

The first draft of this spec was written before the dashboard existed and before the admin panel shipped. A grilling session on 2026-09-04 reversed the following. None of it should be re-opened in review.

1. **The fee is snapshotted, not read live at payment.** The old draft read it from config when a withdrawal was paid, and accepted as a known risk that a fee rise would re-price queued requests and could strand one at a negative payout. That risk is now impossible, and the old draft's user story promising "the amount that arrives is never a surprise" is finally true.
2. **A fourth state, `Accepted`.** The old draft had three and no way to distinguish a request being worked from one nobody had touched. `Rejected` is reachable from `Accepted` as well as `Requested`, so discovering a problem after accepting is not too late.
3. **The Sheba is per request, not per user.** The old draft stored bank details on the user and snapshotted them onto each withdrawal; ticket 01 of the old issue set existed to build that form. It is deleted. No account-holder name is captured at all.
4. **Sheba validation includes mod-97 check digits.** The old draft checked prefix and length only. With no cancel and a Sheba retyped on every request, form validation is the only defence against a transfer to a stranger.
5. **A `note`.** New concept, new glossary term, new column. The old draft recorded the state and time of a decision and nothing else, which left a rejected owner with one bit of information and no way to learn more.
6. **The owner's screen is `/dashboard/finance`, reached from a مالی menu item.** The old draft named no route because no dashboard existed. It carries the balance, the request history, and the request form — and not the itemised statement, which is cut.
7. **Balance figures on the admin user detail view are dropped.** The old draft's ticket 05 existed because "the queue shows an amount with nothing to weigh it against"; the queue row now carries everything needed to decide, so its premise is gone.
8. **The queue links to nothing.** The old draft's ticket 05 required reaching a user's detail view from a request, which would have 404'd for an admin-owned request under ADR-0008.
