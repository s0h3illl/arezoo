# 04 — Settlement lifecycle

**What to build:** Proof that money only counts once the gateway says so, and that a failed attempt leaves a clean database with an intact audit trail.

A contribution is provisional until its payment verifies. When the gateway confirms, the contribution and its payment settle together — a crash must never leave one settled without the other. When the gateway fails, the contribution row disappears entirely and the failed payment survives as the record that the attempt happened (see ADR-0001). This behaviour is already written in the model; this ticket makes it verified rather than assumed, and fixes whatever the tests expose.

**Blocked by:** 02 — Contribution test data

**Status:** ready-for-agent

- [ ] Settling a contribution marks it paid, marks its payment verified, and stores the gateway's reference — all or nothing
- [ ] Abandoning a contribution removes it while its payment survives, marked failed, still carrying its transaction identifier
- [ ] A wish's received total counts only paid contributions — pending ones do not inflate it, and abandoned ones leave no trace in it
- [ ] A wish may receive more than its price; the total is not capped (see ADR-0004)
- [ ] A contributor who gives more than once counts as one contributor
- [ ] A wish with no contributions reports a zero total and zero contributors
- [ ] Visibility never affects the total or the contributor count — a hidden contribution's money still counts
- [ ] Pint clean, larastan clean
