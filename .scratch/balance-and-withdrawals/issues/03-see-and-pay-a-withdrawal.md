# 03 — See and pay a withdrawal

**Status:** done. `tests/Feature/Admin/WithdrawalsTest.php`, `tests/Feature/FinanceTest.php`, `tests/Browser/Admin/WithdrawalsTest.php` green; Pint, larastan clean.

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

The queue an admin works through, and the happy path all the way to money in someone's bank. Each row carries everything needed to make the transfer by hand — who is owed what, the fee, the exact amount to send, and the Sheba to send it to — so the admin never has to calculate or navigate anything. They accept the request, do the transfer in their bank's own interface, come back, and mark it paid.

`AdminHeader.vue` already carries a برداشت‌ها item pointing at `href: '#'`. This slice fills it; it is not a new nav entry.

There is no detail or show page anywhere in this section — one row carries everything. The row does **not** link to the owner's user detail view: ADR-0008's route-model binding 404s for an admin target, and an admin can own a withdrawal like anyone else.

Rejection is ticket 04, the note is ticket 05, and ordering, filtering and the blocked badge are ticket 06. This slice may list rows in any stable order.

Prior art: `Admin\ContributionController` and `ContributionResource` for the read-only resource shape, `admin/users/Show.vue` for the `window.confirm` idiom.

## Acceptance criteria

- [x] An admin sees every withdrawal on a queue page, replacing the `href: '#'` placeholder in `AdminHeader.vue`
- [x] Each row shows the owner's name, the requested amount, the fee, the exact amount to transfer, and the Sheba — money in Toman
- [x] The fee and the transfer amount come from the fee recorded on the withdrawal, never from live configuration
- [x] Each row shows when the request was made, and when it was decided once it has been
- [x] The owner's name is plain text — no row links to a user detail view
- [x] A withdrawal owned by an admin appears in the queue like any other and can be decided like any other; `excludingAdmins()` belongs to the users list and is not applied here
- [x] The list is paginated, following `Admin\ContributionController`'s shape
- [x] A row carries the state-dependent action: accept for a `Requested` row, mark paid for an `Accepted` one
- [x] A `Paid` row shows no action button
- [x] An admin can accept a `Requested` withdrawal, and the time is recorded
- [x] An admin can mark an `Accepted` withdrawal paid, and the time is recorded
- [x] Marking paid a withdrawal that has not been accepted is refused
- [x] A `Paid` withdrawal cannot be changed again by any route
- [x] Accepting reserves the money exactly as `Requested` and `Paid` do — accepting never changes a balance, and this gets a test
- [x] Accept and mark paid each confirm through `window.confirm` with a Persian question, matching `admin/users/Show.vue`
- [x] The owner sees the state and every recorded date on their finance page
- [x] A non-admin cannot reach the queue or act on a request (404), and a guest gets the same 404
- [x] A browser smoke test visits the queue and asserts no JavaScript errors; it does **not** click a `window.confirm` button, which Playwright auto-dismisses and the Pest browser plugin cannot answer
- [x] Pint clean, larastan clean

## Blocked by

- `02-request-a-withdrawal.md`
