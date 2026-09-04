# 02 — Request a withdrawal

**Status:** ready-for-agent

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

An owner asks for their money. They choose an amount, type the Sheba it should go to, see exactly what will land in their bank account after the fee, and confirm. The request waits for an admin; meanwhile it counts against their balance so the same money cannot be requested twice. Every request they have ever made is listed on the finance page beneath the figures.

The Sheba is typed on every request and stored on the request. It is **not** saved to the user, there is no bank-details form, and no account-holder name is captured anywhere. Because a filed request cannot be cancelled or corrected by its owner, the form's validation is the only thing standing between a typo and a transfer to a stranger — hence check digits, not merely a length check.

The fee is fixed, lives in configuration, is **copied onto the withdrawal when it is created** (ADR-0013), and comes out of the payout: requesting 100,000 Toman reduces the balance by 100,000 and produces a transfer of 100,000 minus the fee. That way the requested amount is the only number checked against the available balance, and "take everything" is always expressible. The minimum request is strictly greater than the fee — at exactly the fee the payout would be zero.

This slice creates the withdrawal in `Requested` only. Nothing decides it yet; the admin queue is ticket 03.

The state machine the later tickets complete, recorded here because the enum is created here:

```
Requested ──accept──> Accepted ──mark paid──> Paid      (terminal)
    │                     │
    └──────reject─────────┴──────────────────> Rejected (terminal)
```

`Requested`, `Accepted` and `Paid` all reserve their amount identically; only `Rejected` releases it.

## Acceptance criteria

**Schema and vocabulary**

- [ ] A `withdrawals` table exists, each row belonging to a user and carrying its amount, its fee, its Sheba, its state, its note, and its requested and decided timestamps
- [ ] `WithdrawalStatus` is an enum in `App\Enums` with cases `Requested`, `Accepted`, `Paid`, `Rejected` — the first case is deliberately **not** `Pending`, which already means the opposite in `ContributionStatus`
- [ ] `note` is text, defaults to `''`, and is never null
- [ ] A `WithdrawalFactory` has a state per status, following `ContributionFactory`'s naming
- [ ] Amounts and fees are integer Toman (ADR-0002)

**Making a request**

- [ ] An owner can request a withdrawal for an amount they choose
- [ ] An owner types the destination Sheba on the request itself; nothing is prefilled, and nothing is saved to their account
- [ ] The Sheba is validated for its `IR` prefix, its length, and its ISO 13616 mod-97 check digits, with a Persian message when it fails
- [ ] The rule lives in `app/Rules` — the app's first — with its message in `lang/fa/validation.php`
- [ ] Nothing verifies that the account exists or belongs to the requester, and no such service is added
- [ ] The form shows the fee and the exact amount that will reach the bank, updating as the amount changes, before confirming
- [ ] The fee lives in `config/withdrawals.php` as `fee`, set to 12,500, alongside `hold_hours`
- [ ] The fee is copied onto the withdrawal when it is created, and that row is never re-priced by a later config change — this gets its own test
- [ ] An owner can request their entire available balance in one action, without doing arithmetic themselves
- [ ] A request above the available balance is refused with a Persian message explaining that held money is not withdrawable yet
- [ ] A request at or below the fee is refused; a request one Toman above the fee is accepted
- [ ] Held money is never offered as requestable — the maximum the form allows is always a number that will actually be accepted
- [ ] A `Requested` withdrawal counts against the balance immediately, so the same money cannot be requested twice
- [ ] A second request while one is already `Requested` is refused with an explanation, not queued
- [ ] Two requests submitted simultaneously cannot both succeed — the available balance is re-checked inside the same transaction that creates the request, under a lock on the requesting user, and one test proves it
- [ ] One user cannot request a withdrawal against another user's balance

**Seeing the requests**

- [ ] Every withdrawal the owner has ever made is listed on the finance page, newest first
- [ ] Each row shows the amount, the fee, what will reach the bank, the Sheba as given, the state, and the request date
- [ ] An owner who has never requested anything sees an explained empty state, not a blank panel
- [ ] One user cannot see another user's withdrawals by any route
- [ ] A browser smoke test visits the form and asserts no JavaScript errors
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

## Blocked by

- `01-the-balance-on-the-finance-page.md`
