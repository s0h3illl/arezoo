# 06 — Contributions list

**What to build:** A screen that traces money through the app: every contribution, with the wish it went to, who gave it, how much, whether it landed, and **when** it landed. Read-only — no edit, no delete. A settled contribution is final (ADR-0001, ADR-0004), and nothing an admin clicks should be able to change a wish's received total or an owner's money.

This ticket also records the settlement time, which the app does not capture today. A contribution's creation timestamp marks when the gateway attempt began, not when the payment verified, so it cannot answer "when did this money land". The settlement path already moves a contribution and its payment together in one transaction; the new timestamp joins that write so a crash can never leave a contribution paid with no settlement time. The balance work depends on this timestamp.

**Blocked by:** 01 — Admin gate and the admin section

**Status:** ready-for-agent

- [ ] Settling a contribution records when it settled, written in the same transaction that marks the contribution paid and its payment verified
- [ ] A contribution that has not settled has no settlement time
- [ ] The contribution factory can produce a contribution that settled at a chosen time, so later tickets can place money either side of a boundary
- [ ] An admin sees every contribution, paginated, newest first
- [ ] Each row shows the wish, the contributor, the amount in Toman, the status, and the settlement time
- [ ] The list can be filtered by status, separating money that landed from money still in flight
- [ ] The list can be narrowed to a single wish, so a received total can be reconciled against the contributions behind it
- [ ] The list can be narrowed to a single contributor
- [ ] A contribution with hidden or owner-only visibility shows its contributor to the admin
- [ ] There is no way to edit or delete a contribution from this screen
- [ ] A non-admin cannot reach the screen (404), and a guest is redirected to login
- [ ] A browser smoke test visits the list and asserts no JavaScript errors
- [ ] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
