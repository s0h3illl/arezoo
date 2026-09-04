# The fee is fixed when a withdrawal is requested

A withdrawal records its own fee, copied from configuration at the moment it is created, and is paid out at that figure however long it sits in the queue. Configuration prices new requests and nothing else.

The owner is shown the fee and the exact amount that will reach their bank before they confirm. Reading the fee live at payment time would make that a guess rather than a promise: a fee raised while a request waited would quietly reduce what arrived, and the owner has no way to cancel, amend, or even ask about a request once it is filed. A number shown at the point of consent has to be the number that applies.

It also closes a hole. When the fee comes out of the payout and the minimum request is "more than the fee", a raise can leave an already-queued request whose amount no longer covers its own fee — a payout of zero or less, on a row that passed validation honestly when it was made. The first draft of the withdrawals spec accepted this and asked the admin to notice it by eye. Recording the fee makes it unreachable instead of merely unlikely.

This is the same instinct as recording the Sheba on the request rather than on the user: a withdrawal is an agreement made at a moment, and the terms of that agreement belong to the row, not to whatever configuration happens to say later.

## Considered Options

- **Read the fee from configuration when the withdrawal is paid** — the first draft's decision, rejected here. It is one fewer column and it re-prices work already agreed to, which is the thing a fee shown before confirmation is supposed to rule out.
- **Read it live, and guard against a request whose amount no longer exceeds the fee** — rejected as paying for a column's worth of complexity in validation while keeping the surprise the column was meant to remove.
- **Store the payout instead of the fee** — rejected because the fee is the term that was agreed; the payout is arithmetic anyone can redo, and storing a derived figure invites it to disagree with the two it came from.

## Consequences

- Changing the configured fee has no effect on any existing withdrawal, in any state. A queue worked after a change contains rows priced under both.
- Two withdrawals of the same amount can produce different bank transfers, and both are correct. Anything reporting on payouts must read the fee from the row.
- Configuration is no longer the single source of truth for what a withdrawal costs. It is the source of truth for what the *next* one will cost.
