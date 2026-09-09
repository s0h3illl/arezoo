# 06 — A deleted contributor is nulled

**What to build:** The half of `.scratch/contributions/issues/05` that protects other people's money. Deleting a user must never destroy the contributions they made, because those rows are what credit a *different* owner's balance (ADR-0005) — a cascade lets anyone shrink a stranger's money by closing their own account, and does it silently: the figure is just smaller, with no row left to explain why.

`contributions.contributor_id` becomes nullable and `nullOnDelete`. A deleted contributor's rows survive with everything but their identity — amount, message, visibility, settlement all intact — and render as coming from a deleted user everywhere they are shown. This overrides Visibility in one specific case: a contributor who chose `hidden` and later deletes their account still reads as deleted, not as «ناشناس». See ADR-0012, which supersedes the user half of ADR-0003 and explains the trade-off in full.

This is a narrower slice of ticket 05, split out because 05 also bundles unrelated work — `wishes.user_id`'s missing foreign key, and what happens to a user's own wishes when their account is deleted. That work stays on ticket 05. This ticket is done when nulling and rendering are both correct; it does not touch how a user's account is deleted, only what happens to the contributions they made.

**Blocked by:** None. 02 — Contribution test data is `done`, and the migration already has the `nullOnDelete` foreign key (fixed to also be `nullable()` — a bare `nullOnDelete` on a `NOT NULL` column fails at delete time, not migrate time, which is why this needs its own test rather than trusting the migration).

**Status:** done

- [x] `contributions.contributor_id` is nullable and `nullOnDelete`
- [x] Deleting a user leaves every contribution they made in place, with `contributor_id` null
- [x] A wish's received total and contributor count are unchanged by the deletion of a contributor
- [x] An owner's balance is unchanged by the deletion of one of their contributors — deleting an account never shrinks a stranger's money, and this gets a test that creates the exact failure the cascade used to cause
- [x] A contribution with no contributor renders as coming from a deleted user, in every reader's view — wish pages, the inbox, the admin contributions list
- [x] "Deleted" and «ناشناس» are distinct strings. A nulled contributor reads as deleted even on a row whose Visibility is `hidden`, overriding ADR-0010 for this case only
- [x] A hidden-but-live contributor is unaffected: `ContributionResource`'s existing masking still reads «ناشناس» for a row whose Visibility hides it
- [x] `ContributionResource` and `MessageResource` handle an absent contributor without erroring, and distinguish it from a masked one
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean

## Comments

Split from `.scratch/contributions/issues/05` on 2026-09-05, at the user's request, to be built ahead of the `balance-and-withdrawals` tickets rather than bundled with 05's unrelated FK and cascade work. Ticket 05 is trimmed to match — see its own Comments section.

**Built 2026-09-05.** The migration already carried `nullable()` + `nullOnDelete`, so the schema needed no change — `tests/Feature/DeleteContributorTest.php` pins it. Two things the ticket did not spell out came out of the build:

- `Wish::contributorCount()` counted `distinct contributor_id`, which SQL's `COUNT` skips for nulls, so a deletion silently shrank the figure. It now counts named contributors distinctly and adds each nulled contribution on its own — the count can no longer shrink when somebody leaves, at the cost of a departed contributor who gave twice reading as two people. Their identity is gone, so the two rows cannot be grouped; `tests/Feature/DeleteContributorTest.php` pins both halves of that trade — the figure holding for a one-contribution contributor, and doubling for a repeat giver.
- The admin resource sends `contributor: null` rather than a "deleted" name, since an admin sees through Visibility and the absence is the whole fact. The public and inbox resources gained a third `state`, `deleted`, alongside `visible` and `anonymous`.
