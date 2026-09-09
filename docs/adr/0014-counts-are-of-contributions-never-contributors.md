# Counts are of contributions, never contributors

Every "how many" the app shows about a wish counts contribution rows. Nothing counts distinct people. A contributor who gives twice is two, on every screen and in every model method, and the wish page's pill reads the paginator's `contributions.meta.total` so the number above the list can never disagree with the rows beneath it.

The reason is that a count of people cannot survive a deletion honestly. Deleting a user nulls `contributions.contributor_id` (ADR-0012), and SQL's `COUNT` skips nulls, so `COUNT(DISTINCT contributor_id)` silently shrinks when somebody leaves — a wish that had three contributors yesterday has two today, with the money unchanged and nothing on the page to explain the difference. Patching that by counting named contributors distinctly and adding each nulled row on its own stops the shrink, but buys it with a worse lie: one departed person who gave twice becomes two strangers, because their rows can no longer be grouped. Both figures are wrong in a way a reader cannot detect. A row count is never wrong, because rows are what the app actually has.

It costs the thing the label used to promise. «۵ مشارکت» does not tell a visitor how many people cared, and one enthusiastic repeat giver now reads the same as five separate ones. That was accepted because the honest smaller claim beats a larger claim that quietly changes when an unrelated account closes.

## Considered Options

- **`COUNT(DISTINCT contributor_id)`** — rejected. It shrinks when a contributor deletes their account, and shrinks by an amount nobody can reconstruct from what is left.
- **Distinct named contributors, plus each nulled row counted separately** — rejected after being built. It holds the figure steady across a deletion, which is what it was for, but makes one departed repeat giver read as two people. This was the state of `Wish::contributorCount()` until it was replaced.
- **Snapshot a contributor identifier onto the contribution so departed rows stay groupable** — rejected for the same reason ADR-0012 rejected snapshotting a name: it duplicates data the `users` table owned in order to half-preserve something. Either the identity is kept or it is not.
- **Show no count at all** — rejected. The pill is the page's one signal that a wish has real support behind it, and it does that job without needing to be a headcount.

## Consequences

- The Persian label is «مشارکت» (contribution), never «نفر» (person). Copy that says "people" is a bug, not a wording preference.
- `Wish::contributionCount()` counts paid rows. There is no method that counts contributors, and adding one reopens this decision.
- A count and a sum over the same wish are now the same population — both are `paidContributions()` — so a screen showing both can never be counting different things.
- A deletion cannot move any figure on any page. `receivedTotal()` was already safe; the count is now safe for the same reason, which is that neither reads `contributor_id`.
- Nothing distinguishes a wish supported by five people from one supported by one person five times, and the app cannot answer that question at all after a deletion. If it ever needs to, this decision is the thing to revisit rather than something to work around at a call site.
