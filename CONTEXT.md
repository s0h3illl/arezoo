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
Who can see *who* made a contribution — never how much, and never whether its money counts (ADR-0010). `public`: everyone. `owner`: only the wish's owner. `hidden`: no one, not even the owner. A contribution's amount is always shown, attributed to no one when its contributor is hidden.
A message is not narrower: the wish's owner reads every message left on a paid contribution to their wish, at every Visibility, and Visibility governs whose name sits above it rather than whether it is read (ADR-0011). Under `hidden` the message is shown attributed to no one.

**Dashboard**:
A signed-in user's own section of the app, at `/dashboard` — their account details, the messages left on their wishes, and their money. The money part is named مالی and holds a balance and the withdrawals drawn against it, rather than being named for withdrawals alone: an owner goes there to see what they have earned as often as to ask for it. Never called a profile: that word belongs to the public page at `/u/{username}`, which is a different thing for a different reader.
_Avoid_: Profile, account area, my page

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
An owner's request to take money out of their balance to a bank account they name when they ask. A withdrawal is requested, then accepted, then paid; it may be rejected instead, either before or after it is accepted. Paid and rejected are final. Until it is rejected it holds its money aside, so the same money cannot be asked for twice. It costs a fee, fixed when it is requested and taken out of what the owner receives. Only an admin ends a withdrawal — an owner may ask for one and nothing more.
_Avoid_: Payout, settlement, transaction, pending (the first state is *requested*; see Held)

**Note**:
An admin's remark on a withdrawal, read by the owner it belongs to. Optional, never written by anyone but an admin, and free to change at any point in a withdrawal's life — including after it has been paid or rejected. It is the only thing an admin can say to an owner about their money.
_Avoid_: Message (which is a contributor's words on a contribution), comment, reason

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
