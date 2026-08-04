# Owners may not contribute to their own wishes

A user cannot make a contribution to a wish they own. This looks like an arbitrary restriction and is not: without it, a card payment to your own wish becomes a balance, and that balance becomes a bank transfer. The platform would be moving money from a card to a bank account on one person's instruction, minus a fixed fee — which is a money-transfer service, not a wishlist, and the kind of pattern a payment provider closes a merchant account over.

Before balances existed the pattern was harmless: money went in and nothing came out. The rule exists because withdrawal exists.

## Considered Options

- Allow it, but credit no balance — rejected because it breaks the one rule that keeps a derived balance simple ("every paid contribution credits the wish's owner", ADR-0005) and leaves money in the system belonging to nobody.
- Allow it — rejected. The only thing it enables that a user could not achieve by simply not using the app is the cash-out route above.
