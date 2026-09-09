# 05 — The note

**Status:** done. `tests/Feature/Admin/WithdrawalsTest.php`, `tests/Feature/FinanceTest.php`, `tests/Browser/Admin/WithdrawalsTest.php`, `tests/Browser/FinanceTest.php` green; Pint, larastan clean.

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

The only thing an admin can say to an owner about their money. A third button on every queue row opens a modal containing a single textarea; whatever is saved there is read by the owner on their finance page, beside the request it belongs to.

It exists because of what the rest of the design does not have. An owner cannot cancel a request, cannot amend one, and has no way to ask a question; their single remaining move after a rejection is to ask again. Without a note, a rejection carries one bit of information and neither side can break the loop — the owner re-requests the same thing, because nothing told them what to change.

It is writable in **every** state, including `Paid` and `Rejected`. Finality governs a withdrawal's state and its money, not its note: the most likely moment to need one is just after a rejection made in a hurry. It is always optional — an unexplained rejection stays possible by design.

The word is `note`, and it is a `CONTEXT.md` term. It is not a "message" — that word belongs to a contributor's words on a contribution (ADR-0011) — and not a "comment" or a "reason".

## Acceptance criteria

- [x] Every queue row carries a note button, in every state, giving three buttons on a live row
- [x] The button opens a modal containing a single textarea and a save action
- [x] The modal does **not** confirm — it saves
- [x] The note can be written and rewritten while `Requested`, `Accepted`, `Paid` and `Rejected`
- [x] Saving a note never changes a withdrawal's state, amount, fee, Sheba, or any of its timestamps
- [x] The note is always optional, and a rejection with an empty note is allowed
- [x] The owner reads the note beside the request it belongs to on their finance page
- [x] A withdrawal with an empty note renders nothing rather than an empty element
- [x] An owner cannot write or change a note by any route
- [x] One user cannot read another user's notes
- [x] No history of note edits is kept, and no second table is introduced
- [x] A non-admin cannot write a note (404), and a guest gets the same 404
- [x] A browser smoke test opens the modal and asserts no JavaScript errors
- [x] Pint clean, larastan clean

## Blocked by

- `03-see-and-pay-a-withdrawal.md`
