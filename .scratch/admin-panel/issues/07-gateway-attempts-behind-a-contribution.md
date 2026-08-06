# 07 — The gateway attempt behind a contribution

**What to build:** The screen that answers "I paid and nothing happened". When a gateway attempt fails, the contribution is deleted and the failed payment survives as the only record that the attempt ever happened (ADR-0001) — which means a user's failed payment is currently invisible to everyone. This makes it visible, and lets an admin match any record in the app against the gateway's own dashboard.

**Blocked by:** 06 — Contributions list

**Status:** ready-for-agent

- [x] An admin can see the gateway attempt behind any contribution, including its transaction and reference identifiers
- [x] An admin can see gateway attempts that failed and left no contribution behind
- [x] Failed attempts can be found by their transaction identifier, so a user's complaint can be matched to a record
- [x] Failed attempts show their amount in Toman and when the attempt was made
- [x] A pending attempt — one that reached the gateway and never came back — is distinguishable from a failed one
- [x] Gateway attempts are read-only; there is no way to edit or delete one
- [x] A non-admin cannot reach the screen (404), and a guest gets the same 404
- [x] A browser smoke test visits the screen and asserts no JavaScript errors
- [x] Pint clean, larastan clean

## Comments

Two surfaces, because the ticket asks two different questions. "The gateway attempt behind a contribution" is answered on the contributions list, which gained one column carrying both identifiers — transaction above, reference beneath — so a row can be matched against the gateway's dashboard without a second lookup. "The attempt that left no contribution behind" cannot live there at all, since there is no contribution to hang it off, so it gets a screen of its own: `admin/payments`, a fifth resource/controller pair following `ContributionResource`/`ContributionController`'s shape, `Route::resource(...)->only(['index'])` so no update or destroy route exists to hit.

`PaymentResource::$contribution` is nullable and `ContributionResource::$payment` is not, which is ADR-0001 written into the two type shapes: a failed attempt outlives its contribution, so an attempt may have none, while a contribution always has an attempt behind it.

Search is on `transaction_id` alone, via a `matching` scope mirroring `User::matching()`, wildcard escaping and all. Reference identifiers were deliberately left out: a failed attempt never gets one — the gateway hands it back only on success — so searching it would not serve the case the screen exists for. The search test runs over all three endings rather than failed alone: the user asking has no idea how their attempt ended, which is the question.

`PaymentStatus` gets three distinct badges rather than two, because "reached the gateway and never came back" (`pending`) and "the gateway turned it down" (`failed`) are different answers to give a user: the first may still land.

The Toman and Jalali formatters the contributions page carried inline moved to `resources/js/lib/format.ts` when the payments page needed the same two, rather than being copied.
