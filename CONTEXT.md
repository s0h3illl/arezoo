# Arezoo

A wishlist app where people publish wishes and others help pay for them.

## Language

**Wish**:
An item a user wants, published so others can put money towards it. Its details are the owner's to change at any time, whatever has been received (ADR-0009), so its price is a target rather than a commitment.

**Contribution**:
Money a contributor has put towards a wish. Exists only while the money is real or in flight — a failed attempt leaves no contribution behind.
_Avoid_: Donation, pledge, gift

**Contributor**:
The user who made a contribution.
_Avoid_: Donor, backer

**Payment**:
A single attempt to move money through the payment gateway. Every attempt is kept, whether it succeeds or not.
_Avoid_: Transaction

**Owner**:
The user a wish belongs to, and whose profile it appears on.

**Profile**:
A user's public page, at `/u/{username}`, holding every wish they have published. Anyone may read it without an account — it exists to be sent to people. Only its owner may add to it or remove from it.

**Username**:
The name a user picks for themselves when they sign up, and the only part of their profile's address. Unique, and theirs to change — which frees the one they gave up, so a link shared under an old username may later lead somewhere else.
_Avoid_: Handle, slug

**Visitor**:
Anyone reading a profile that is not theirs, whether they are signed in or not. A visitor sees the same page in either case.
_Avoid_: Guest (which means signed out, whosever profile they are reading)

**Visibility**:
Who can see a contribution's contributor details (name, amount, message) — never whether its money counts. `public`: everyone. `owner`: only the wish's owner. `hidden`: no one, not even the owner.

**Toman**:
The unit of every money value in the app. Rial appears only at the payment-gateway boundary.
_Avoid_: Rial (except when talking to the gateway)

**Balance**:
What an owner has earned from contributions to their wishes and not yet withdrawn. Only contributions to your own wishes feed it; the only way money leaves is a withdrawal. Every balance is the sum of an available and a held part.
_Avoid_: Wallet, credit, funds

**Available**:
The part of a balance a withdrawal may draw on. Money becomes available once it has been held long enough.

**Held**:
The part of a balance that is real money the owner has earned, but cannot withdraw yet. Held money always becomes available with the passage of time alone — nothing can take it back.
_Avoid_: Pending (which means the money isn't real yet — see Contribution)

**Withdrawal**:
An owner's request to take money out of their balance to their bank account. A withdrawal is either awaiting a decision, paid, or rejected; it costs a fee, taken out of what the owner receives.
_Avoid_: Payout, settlement, transaction

**Raised**:
The lifetime sum of every contribution whose money has landed, across the whole platform. It only grows: a withdrawal moves money out of one owner's Balance, and does not move this.
_Avoid_: Balance (a Balance is what an owner may withdraw; this is what the platform has ever taken in, and never goes down)

**Admin**:
A user who can see every user other than an admin, every contribution and withdrawal, and who decides whether a withdrawal is paid. Not a role anyone can reach by signing up, and not one the panel manages: a staff account is created, blocked, or given a password out of band. Being staff reaches no further than the panel — out in the app an admin publishes wishes, has a profile, and is contributed to like anyone else.

**Terms of use**:
The rules a user accepts by signing up, published at `/terms`. One Markdown file in the repository, so the wording is reviewed in a diff and changes on deploy. There is no draft and no history of its own: what is on the branch is what the reader sees, and git remembers the rest.

**Blocked**:
A user an admin has barred from the app. A blocked user cannot sign in, and nothing of theirs is reachable by anyone else. Blocking never touches their money.
_Avoid_: Banned, suspended, deactivated
