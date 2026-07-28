# All money is stored in Toman

Every money value in the database — `wishes.price`, `payments.amount`, `contributions.amount` — is an integer amount of Toman. There is no currency column; this is a single-currency app. The payment gateways (via shetabit/payment) settle in Rial, so amounts are converted Toman → Rial only at the gateway boundary, and never stored in Rial anywhere.

## Consequences

- Any code initiating or verifying a gateway payment must apply the ×10 conversion (or configure shetabit's currency as Toman) — an amount that looks "10× off" at the boundary is the conversion, not a bug.
