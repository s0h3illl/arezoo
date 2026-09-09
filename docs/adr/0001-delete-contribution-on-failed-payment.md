# Delete the contribution when its payment fails

A contribution is provisional until its payment verifies. When a gateway attempt fails, the contribution row is deleted rather than kept with a `failed` status — so `ContributionStatus` is only `pending | paid`, and the `contributions` table only ever holds real or in-flight money. Totals, contribution counts, and listings never need to filter out dead rows.

The audit trail lives in `payments` instead: the payment deliberately outlives the contribution it was created alongside (marked `failed`), which is why `payments` holds no foreign key to `contributions` — the contribution points at the payment.

## Considered Options

- Keep the contribution with a `failed` status — rejected because every read path (sums, counts, listings) would have to remember to exclude failed rows forever.
