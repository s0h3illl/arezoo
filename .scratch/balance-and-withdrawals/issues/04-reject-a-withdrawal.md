# 04 — Reject a withdrawal

**Status:** done. `tests/Feature/Admin/WithdrawalsTest.php`, `tests/Feature/FinanceTest.php`, `tests/Browser/Admin/WithdrawalsTest.php` green; Pint, larastan clean.

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

The way a request leaves the queue without money moving. An admin rejects it — before accepting, or after accepting and then discovering a problem — and the amount returns to the owner's available balance immediately, with no separate action and nothing to undo. The owner sees the outcome on their finance page and can ask again.

Rejection releases the reserve by the same arithmetic that created it: a rejected withdrawal simply stops counting against the balance (ADR-0005). There is no compensating write.

Rejection is reachable from both live states, which is the point — the gap between accepting a request and transferring the money is exactly where a problem gets discovered, and it must not be too late to stop.

An owner cannot reject, cancel, or otherwise end their own request, and has no in-app way to ask for one to be stopped. That is deliberate; do not add a route for it.

## Acceptance criteria

- [x] A row carries a reject action while it is `Requested` or `Accepted`
- [x] An admin can reject a `Requested` withdrawal, and the time is recorded
- [x] An admin can reject an `Accepted` withdrawal, and the time is recorded
- [x] Rejecting confirms through `window.confirm` with a Persian question, matching `admin/users/Show.vue`
- [x] Rejecting returns the amount to the owner's available balance immediately, with no separate action needed
- [x] A `Rejected` withdrawal shows no accept, mark-paid, or reject action
- [x] A `Rejected` withdrawal cannot be changed again by any route, including back to `Requested`
- [x] A `Paid` withdrawal cannot be rejected
- [x] After a rejection the owner can request again, and a test proves the previously reserved money is requestable
- [x] The owner sees the rejected state and its date on their finance page
- [x] There is no route by which an owner can end their own request
- [x] A non-admin cannot reject a withdrawal (404), and a guest gets the same 404
- [x] Pint clean, larastan clean

## Blocked by

- `03-see-and-pay-a-withdrawal.md`
