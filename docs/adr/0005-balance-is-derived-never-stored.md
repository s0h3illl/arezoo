# A balance is derived, never stored

There is no `balance` column and no ledger table. An owner's balance is computed on read: available is the sum of paid contributions to their wishes that have been held long enough, minus every withdrawal that has not been rejected; held is the rest. A withdrawal counts against the balance from the moment it is requested, and goes on counting once it is accepted and once it is paid, so the same money can never be requested twice; rejecting a withdrawal releases it by the same arithmetic.

> **Amended 2026-09-04.** This originally read "minus every withdrawal that is awaiting a decision or paid", written when a withdrawal had three states. A fourth, `Accepted`, sits between requested and paid. Every state but `Rejected` reserves its amount identically, which is why the rule is now stated as an exclusion rather than a list — a fifth state would otherwise silently stop reserving.

This is the same instinct as `Wish::receivedTotal()` — the contributions are the truth, and every figure the app shows is a question asked of them. A derived balance cannot disagree with the contributions that justify it, so there is no reconciliation to write and no drift to detect.

It costs something. Every money concept must fit as either a contribution or a withdrawal: there is no row on which to hang an admin adjustment, promotional credit, or reversal. That is affordable only because settled money is final (ADR-0001, ADR-0004) — the app has no refund machinery to represent.

## Considered Options

- A `users.balance` column mutated on settlement and withdrawal — rejected because it is the only option that can be silently wrong: a crash between a contribution settling and the increment leaves money uncounted, with nothing to reconcile against.
- A `balance_entries` ledger, one row per credit and debit — rejected as premature. It buys room for adjustments and reversals that this app deliberately does not have, and its rows can disagree with the contributions they mirror.

## Consequences

- Settlement needs its own timestamp on a contribution. `created_at` records when the gateway attempt began, not when it verified, and the held/available boundary must be measured from verification.
- If an adjustment, reversal, or promotional credit is ever needed, this decision is the thing to revisit first — not to patch around.
