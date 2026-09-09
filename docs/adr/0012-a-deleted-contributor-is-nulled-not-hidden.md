# A deleted contributor is nulled, not hidden

Deleting a user sets `contributions.contributor_id` to null rather than removing the contribution or hiding the account behind a soft delete. The contribution survives with its amount, its message, its visibility and its settlement intact; only the identity of the person who made it is gone, and gone permanently. Wherever that contribution is shown — a wish page, an owner's inbox, the admin contributions list — it reads as having come from a deleted user.

This is what protects other people's money. Balances are derived from contribution rows and nothing else (ADR-0005), so a cascade would let anyone shrink a stranger's balance by closing their own account, and would do it silently: the owner's figure would simply be smaller than it was yesterday, with no row left to explain the difference. Nulling keeps every row that ever credited a balance.

It supersedes the user half of ADR-0003, which chose soft deletion for the same goal. Soft deletion also keeps the row, and keeps more: `withTrashed()` could still answer "who was this". That answer is what is being given up. An admin investigating suspicious money can trace a contribution to a live account and no longer to a departed one, which narrows what the 72-hour hold of ADR-0006 was bought to enable. It was accepted because the alternative is soft-delete machinery on `User` — an account that still occupies its username and email, a `withTrashed()` decision at every call site that reads a contributor, and a purge path to build anyway. The wish half of ADR-0003 is untouched: wishes remain soft-deleted, so a wish and its contributor now disappear by different mechanisms.

It also overrides ADR-0010 in one case. Visibility hides identity, and a contributor who chose `hidden` chose it permanently — but a nulled contributor reads as deleted everywhere, including on rows that were hidden. A reader of a hidden row therefore learns one thing about a person they were never allowed to identify: that the account is gone. This was weighed and taken: the alternative is a precedence rule that makes "deleted" mean different words in different places, and a masked row that silently claims a live contributor stands behind it.

## Considered Options

- **Soft-delete `User`, as ADR-0003 decided** — rejected for the machinery, not the principle. It is the better answer for the audit trail and should be the first thing revisited if traceability ever matters more than it does now.
- **Cascade, as the schema does today** — rejected outright. Deleting an account destroys contributions that credited *other* people's balances, so a user with nothing to lose can reduce a stranger's money to a number nobody can reconstruct.
- **Null the contributor but snapshot their name onto the contribution** — rejected as duplicating data the users table owned in order to half-preserve an audit trail. Either the identity is kept or it is not.
- **Apply Visibility first, so a hidden contributor stays masked after deletion** — rejected as two rules where one will do, at the cost of a rendering path that has to know which absence it is looking at.

## Consequences

- `contributions.contributor_id` must be nullable, and every read of a contribution's contributor must handle its absence. `ContributionResource` and `MessageResource` mask an unseen contributor today; they now have a second reason a name can be missing, and it is not the same reason.
- "Deleted" and «ناشناس» are different words with different meanings, and the admin screens are where the difference is load-bearing: an admin sees through Visibility, so «ناشناس» should never appear there and "deleted" is unambiguous when it does.
- A contribution can outlive every party to it: a soft-deleted wish and a nulled contributor, still crediting a live owner's balance.
- Nothing may count distinct contributors, because nulling makes that count shrink or split. Counts are of contribution rows instead — see ADR-0014, which this decision forced.
- Identity is unrecoverable after the fact. If an investigation ever needs to know who a departed contributor was, that information does not exist anywhere in the database, and no migration can bring it back.
