# 07 — The withdrawals page against the balance spec

Type: grilling
Status: resolved
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

## Answer

Resolved 2026-09-04 by a grilling session that went further than this ticket asked. `.scratch/balance-and-withdrawals/spec.md` was not merely amended — it was rewritten, and its issue set replaced. The amendment this ticket owed is therefore the whole file; what follows is only what bears on this section.

**The page is `/dashboard/finance`, not `/dashboard/withdrawals`.** The section is named مالی in the account menu. It holds a balance the owner cannot withdraw yet as well as the requests drawn against it, so naming it for withdrawals alone undersells it — an owner goes there to see what they have earned as often as to ask for it. `CONTEXT.md`'s Dashboard entry is amended to match. The routing table and menu design in this effort's spec are amended below.

**Three things on the page**, and the balance spec's stories were renumbered around them: the balance figures, every withdrawal the owner has made, and the request form.

**The itemised statement stays cut.** Stories 8–10 of the old draft are gone and the new spec lists them as out of scope. The cost this ticket named — "the owner is shown three numbers and asked to trust them" — is partly paid back by the withdrawal history, which is now on the page unconditionally rather than only when there is something in it: an owner can reconcile what left against what their bank received, just not what came in.

**Old stories 11 and 12 are invisible-but-true.** A contribution credits the balance whether or not its wish was deleted or its contributor left. Nothing on the page says so, and nothing needs to — the figure is simply correct. Story 12 turned out to be *false* in the code, not merely invisible: `contributions.contributor_id` cascades, so deleting an account destroys contributions crediting other people's balances. That is fixed in `.scratch/contributions/issues/05` under ADR-0012.

**The three figures read as one headline and two parts**, with a single "next release" line beside held — the earliest date on which any held money matures. Old story 5 wanted a per-contribution release schedule; that was a statement column, and it died with the statement.

**The bank-details form does not exist.** This ticket assumed it moved onto the page. It is gone entirely: the Sheba is typed on each request and stored on the request, never on the user. Old ticket 01 of the balance spec, which existed to build that form, is deleted. Old story 20 ("an owner with no bank details saved is asked for them before they can request") is void — there is nothing to save.

**This deletes a recorded risk of this effort's spec.** The security note reading "An open session can change the Sheba and then withdraw to it" no longer describes anything: there is no stored Sheba to change. Amended below.

**The request form does live arithmetic**, showing the fee and the exact amount that will reach the bank as the amount changes, plus a one-action "request everything available". The fee is copied onto the withdrawal at request time (ADR-0013), so the figure shown at the point of consent is the figure that applies however long the request waits.

**The history rows** show amount, fee, what reached or will reach the bank, the Sheba as given, the state, the request date, the decision date, and the admin's note when there is one. The Sheba is on the row for the reason old story 19 gave, reached differently: it is not a snapshot of a profile field, it is the request's own data.

**Four states, not three.** `Requested → Accepted → Paid`, with `Rejected` reachable from either of the first two. The Persian labels this section needs are three no longer: در انتظار بررسی / پذیرفته شد / پرداخت شد / رد شد.

**A new concept the owner reads here: the Note.** An admin's optional remark on a withdrawal, writable at any point including after a decision, and the only thing an admin can say to an owner about their money. In `CONTEXT.md`.

**Empty states**: a zero balance is explained rather than blank, and an owner who has never requested anything gets an explained empty panel rather than an absent one.
