# 07 — The withdrawals page against the balance spec

Type: grilling
Status: open
Blocked by: none

## Question

`.scratch/balance-and-withdrawals/spec.md` is `ready-for-agent` and owns the arithmetic outright — the available/held derivation, the 12,500 Toman fee, the 72-hour hold, the one-open-request rule, the concurrency lock, the three terminal states, and the whole admin queue. **None of that is re-opened here.**

What is open is the owner-facing screen, because that spec was written before a dashboard existed and [charting](00-charting.md) has already changed two things about it: the itemised statement is cut, and the bank-details form moves onto this page.

Decide:

- **Which of its 48 stories this page actually renders**, and confirm each survives losing the statement. Stories 8–10 go. Story 11 ("a contribution counts even if its wish was deleted") and story 12 ("money stays mine if the contributor deleted their account") were *visible* through the statement — decide whether they are now invisible-but-true, or need saying somewhere.
- **What replaces the statement's job.** It existed so "the arithmetic of my balance is visible rather than asserted" (story 10). With it gone the owner is shown three numbers and asked to trust them. Decide whether that is accepted, or whether something smaller carries the same reassurance.
- **How the three figures read.** Total, available, and held are one headline and two parts (stories 3 and 4). Story 5 wants each held part's release date — which was a statement column. Decide what carries it now: a single "next release" line, a held breakdown, or nothing.
- **The bank-details form in place.** Story 20 says an owner with none saved is asked for them before they can request. Decide whether the form replaces the request button, sits above it, or opens in a dialog — and note `EditWishDialog`/`DeleteWishDialog` are the app's established dialog precedent if it is one.
- **What the request form does.** Stories 22–23: request everything in one action, and see the fee and the exact arrival amount before confirming. Decide whether that is live arithmetic in the form or a confirmation step.
- **The history rows.** State (در انتظار بررسی / پرداخت شد / رد شد), amount, requested date, decided date. Story 19 says a paid withdrawal shows the account it was *requested* for, not the current one — the Sheba is copied onto the withdrawal at request time. Decide whether the row shows it.
- **Where the empty states are.** Zero balance with an explanation rather than a blank screen (story 13), and no requests yet.
- **The amendment itself.** This ticket's answer is what gets written back into `.scratch/balance-and-withdrawals/spec.md`. Record the edit precisely — which stories go, which change — rather than leaving the two specs to disagree.
