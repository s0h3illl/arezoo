# Arezoo

A wishlist app where people publish wishes and others help pay for them.

## Language

**Wish**:
An item a user wants, published so others can put money towards it.

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
The user a wish belongs to.

**Visibility**:
Who can see a contribution's contributor details (name, amount, message) — never whether its money counts. `public`: everyone. `owner`: only the wish's owner. `hidden`: no one, not even the owner.

**Toman**:
The unit of every money value in the app. Rial appears only at the payment-gateway boundary.
_Avoid_: Rial (except when talking to the gateway)
