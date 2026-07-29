# 03 — Money integrity constraints

**What to build:** A database that refuses to double-count money. The models treat a payment and its contribution as one-to-one, but nothing enforces it — two contributions could point at the same payment today, and a single settled payment would then be counted twice in a wish's received total. The same applies to the gateway's transaction identifier: two payments sharing one would mean verifying either could settle the wrong money.

Also covers the read paths that run on every wish view, which filter contributions by wish and status together.

**Blocked by:** 02 — Contribution test data

**Status:** ready-for-agent

- [x] A contribution's payment is unique — a second contribution pointing at an already-used payment is rejected by the database
- [x] A payment's gateway transaction identifier is unique, and still nullable for payments that have not reached the gateway yet
- [x] Contribution amount, payment amount, and wish price are unsigned (see note below)
- [x] Contributions are indexed by wish and status together, covering the received-total and contributor-count reads
- [ ] Tests cover the rejection cases through the model API, not by inspecting the schema
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

**Note on unsigned:** this project runs SQLite in development and test, and SQLite ignores `UNSIGNED` — the column takes integer affinity with no constraint, so a negative amount will still insert. Declaring it unsigned is documentation and portability, not enforcement. Do not write a test asserting that a negative amount is rejected; it will fail. Rejecting negative amounts belongs to request validation in the back-end phase.
