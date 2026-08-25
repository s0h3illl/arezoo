# Visibility hides identity, never amount

A contribution's Visibility governs whether a reader can see *who* gave — never *how much*, and never whether the money counts toward the wish. An anonymous or owner-only-hidden row still shows its real, unrounded amount; only the name, avatar and message disappear.

This looks like it should be symmetric — "hidden" reads as "hidden," full stop — and it is not, for a reason worth recording: the wish's total already counts every contribution regardless of visibility (masking has never touched the ledger), so a masked amount was always arithmetically recoverable. One anonymous row on an otherwise-empty wish means that row's amount *is* the wish's `received`; even on a busier wish, subtracting every visible row from the total narrows a masked one fast. Withholding the number in the UI was never privacy, only the appearance of it — real privacy would mean not showing wish totals or contributor rows at all, which defeats the reason this page exists. Given that, showing the true amount is the honest version of the same page, and it makes an anonymous contribution more legible: a reader can see a wish got real support without knowing from whom.

## Considered Options

- Keep amount governed by Visibility, withheld as `'—'` on masked rows (the original design and an earlier ticket's decision) — rejected once the arithmetic leak was traced through: it protects nothing on any wish with few enough rows to subtract, so the withholding was cosmetic, not a real guarantee.
- Mask both identity and amount, and also fuzz or omit the wish total to close the arithmetic leak — rejected as out of scope for this effort and probably wrong regardless: the total is the whole point of the funding card, on every wish, not just ones with a masked contributor.
