# Earned money is held for three days

A contribution's money belongs to the owner the moment its payment verifies, and appears in their balance immediately — but it is held, not available, until three days (72 hours) after settlement. Only then may a withdrawal draw on it. Maturity is per contribution, measured from its own settlement, so a balance is continuously becoming available rather than releasing in batches.

The hold is not a mechanism to claw money back — nothing can reverse a settled contribution (ADR-0004). It is a window in which a human can act *before* the money becomes reachable: block an account, refund at the gateway directly, investigate a wish that is collecting suspiciously. Without it, money could arrive and leave the platform faster than anyone could look at it.

Held money is shown to the owner rather than hidden, alongside when each part matures. An owner who has earned money should see that they have earned it; concealing it until it matures invites "where did my money go".

## Consequences

- The word "held" is reserved for this and must not be conflated with a pending contribution, which is money that is not real yet.
- Because the hold is bounded and unconditional, blocking account deletion while a balance is outstanding is a temporary block by construction — at most three days, then the owner can take their money and leave.
