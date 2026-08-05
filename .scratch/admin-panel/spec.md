# Spec: Admin panel

Status: ready-for-agent

## Problem Statement

Nobody can see inside the app. There is no way to look up a user, no way to stop a user who is abusing the platform, no way to help a user who has lost access to their email, and no way to answer "where did this money come from" when a contributor says they paid and nothing happened. The app is about to start holding real money on people's behalf, and today the only tool for any of this is a database client.

There is also a hard dependency ahead: withdrawals are useless without a human to approve them. That screen needs somewhere to live.

## Solution

An admin section of the app, reachable only by an admin, in the app's own Persian RTL visual language. It lists every user with the ability to block them, unblock them, and set a new password; and it lists every contribution, read-only, with the gateway attempt behind it. Blocking bars a user from signing in and makes everything of theirs unreachable to everyone else, without touching a Toman of their money.

This spec builds the admin shell and its first two screens. The withdrawals screen is specified separately and plugs into this shell.

## User Stories

1. As an admin, I want a section of the app only I can reach, so that moderation tools are never exposed to users.
2. As an admin, I want to reach the admin section from the app itself once signed in, so that I do not have to remember a URL.
3. As a user who is not an admin, I want the admin section to be indistinguishable from a page that does not exist, so that I never learn there is a panel to attack.
4. As a signed-out visitor, I want admin URLs to look like pages that do not exist, so that the panel's existence is not advertised.
5. As an admin, I want the panel to read in Persian and RTL like the rest of the app, so that it does not feel like a separate product.
6. As an admin, I want a navigation shell shared by every admin screen, so that moving between users, contributions, and withdrawals is one click.
7. As an admin, I want a list of every user, so that I can find anyone who is reported or in trouble.
8. As an admin, I want the user list paginated, so that it stays usable as the app grows.
9. As an admin, I want to search users by name and email, so that I can find one person without scrolling.
10. As an admin, I want each user row to show whether they are blocked, so that I can see the state of the platform at a glance.
11. As an admin, I want to filter the user list to blocked users only, so that I can review past moderation decisions.
12. As an admin, I want to see when a user registered and whether their email is verified, so that I can judge a suspicious new account.
13. As an admin, I want a detail view for a single user, so that I can see everything about them in one place before acting.
14. As an admin, I want a user's wishes and the contributions they have made listed on their detail view, so that I can judge a report without running queries.
15. As an admin, I want to block a user, so that someone abusing the platform stops immediately.
16. As an admin, I want to confirm before blocking, so that a misclick does not lock a real person out.
17. As an admin, I want a blocked user's existing session dropped, so that blocking takes effect now rather than whenever they next sign in.
18. As an admin, I want to unblock a user, so that a moderation decision is reversible when it was wrong or the reason has passed.
19. ~~As an admin, I want to see when a user was blocked, so that I know how long a decision has stood.~~ — **dropped:** blocked state is a boolean, so there is no "when" to show. Recording the moment of every moderation decision is an audit log, which this spec lists as out of scope.
20. As a blocked user, I want a clear Persian message when I try to sign in, so that I know my account is blocked rather than my password being wrong.
21. As a blocked user, I want my money to be untouched by the block, so that being barred from the app is not a confiscation.
22. As a visitor, I want a blocked user's profile to send me home, so that blocking actually hides them.
23. As a visitor, I want a direct link to a blocked user's wish to send me home too, so that an old saved link is not a way around the block.
24. As a visitor, I want a blocked user's wishes absent from every listing and search, so that there is no route back to their content.
25. As a contributor to a blocked user's wish, I want my own contribution history to still show what I gave, so that blocking someone else does not erase my record.
26. As an admin, I want to set a new password for a user, so that someone who has lost access to their email can get back in.
27. As an admin, I want the new password held to the same rules as any password in the app, so that an assisted reset does not create a weak account.
28. As an admin, I want to confirm before setting someone's password, so that I never do it by accident.
29. As a user whose password an admin has set, I want my existing sessions dropped, so that the change actually takes effect everywhere.
30. As an admin, I want a list of every contribution, so that I can trace money through the app.
31. As an admin, I want each contribution row to show its wish, its contributor, its amount, its status, and when it settled, so that one screen answers most questions.
32. As an admin, I want the contribution list paginated and ordered newest first, so that recent activity is what I see.
33. As an admin, I want to filter contributions by status, so that I can separate money that landed from money still in flight.
34. As an admin, I want to see the contributions belonging to one wish, so that I can reconcile a wish's received total.
35. As an admin, I want to see the contributions made by one user, so that I can investigate a single contributor.
36. As an admin, I want to see the gateway attempt behind a contribution, including its identifiers, so that I can match a record against the gateway's own dashboard.
37. As an admin, I want to see gateway attempts that failed and left no contribution behind, so that I can answer a user who says they paid and nothing happened.
38. As an admin, I want contributions to be read-only, so that no click of mine can silently change a wish's total or an owner's money.
39. As an admin, I want a contribution's amount shown in Toman like everywhere else, so that I never have to convert in my head.
40. As an admin, I want a hidden contribution's contributor visible to me, so that visibility governs what users see and not what moderation can see.
41. As an admin, I want to see a contribution whose wish or contributor has been deleted, so that history stays traceable after the fact.
42. As an owner of the system, I want admin status to be ungrantable through the app, so that no bug in a form can promote an attacker.

## Implementation Decisions

- Admin status is a boolean on the user, defaulting to false, with no UI anywhere that sets it — an admin is made in a seeder or directly in the database. There is no role concept and no permissions package.
- Blocking is recorded as a boolean on the user, defaulting to false. Unblocking clears it. The app records _that_ someone is blocked, not _when_ — a moderation timeline is an audit log, which is out of scope.
- Admin screens live under an admin URL prefix behind a single gate: admin. A signed-in non-admin gets a 404, not a 403 — a 403 confirms the panel exists. A signed-out visitor gets the same 404, not a login redirect, which would confirm it just as loudly. Email verification is not a gate here; the admin check subsumes it, since admin is granted out-of-band.
- The admin section has its own Inertia layout — a navigation shell with links to users, contributions, and (once specified) withdrawals — but reuses the app's Persian RTL direction, typography, and colour language rather than introducing a second visual system.
- Blocked users are refused during authentication with a dedicated Persian message distinct from the invalid-credentials message, and their sessions are invalidated at the moment of blocking.
- Hiding a blocked user's content is expressed once, as a single query-level rule that public-facing reads inherit, so a new listing or page cannot forget it. Reaching a blocked user's profile or wish directly redirects home rather than 404-ing — matching the answer given for this feature, and avoiding confirming that the resource exists.
- The public surfaces this hiding applies to — profile pages, wish pages, listings, search — do not exist yet. The rule and its tests land with them; what this spec builds is the block itself, the sign-in refusal, and the shared rule those pages will consume.
- Setting a password is an admin form validated by the same password rules the app already uses for registration and reset, and it invalidates the user's other sessions. It does not notify the user; telling them is done out of band.
- Contributions are read-only in the admin section: no edit, no delete. This follows from ADR-0001 and ADR-0004 — a settled contribution is final and there is no reversal machinery to expose.
- The contributions screen reads through to the underlying gateway attempt, including attempts that failed and therefore have no contribution (ADR-0001), because that record is the only trace of a payment a user believes they made.
- The user list surfaces balance figures once the balance spec lands; this spec does not compute or display money on users.
- Accepted risk, recorded deliberately: an admin who can set any user's password can sign in as that user and, once withdrawals exist, request that user's money to a bank account the same admin can also edit. This was chosen over sending a reset link because a user locked out of their email otherwise has no route back. There is no audit log; with one admin, the mitigation is that the admin is the owner.

## Testing Decisions

- A good test asserts external behaviour at the HTTP boundary: what renders, what a POST does to database state, where it redirects, and what a subsequent request can and cannot see. It never reaches into how a balance, a filter, or a gate is computed.
- One seam: Pest feature tests over the admin HTTP routes. There is no separate model-level seam for blocking or admin status — both are asserted through what the panel does and what the blocked user can no longer do.
- Prior art: the existing feature tests in this repo (Pest with `RefreshDatabase`, Inertia component assertions on GET, redirect and state assertions on POST) and the auth feature tests' handling of session state.
- Access control gets its own tests as a matter of course: signed-out, signed-in non-admin, and admin each hitting every admin route, asserting both non-admin cases see a 404 rather than a 403 or a redirect.
- Factory states are added for an admin user and a blocked user, following the naming already used by the contribution factory's `paid()` and `pending()` states.
- Blocking is tested through its consequence — a blocked user's sign-in attempt fails with the blocked message, and their session no longer authenticates — not by asserting a column.
- The contributions screen is tested for what it lists and how it filters, plus one test that a failed gateway attempt with no contribution is still visible, since that is the case the screen exists for.
- Browser smoke tests, in the style already established for the auth pages, cover the admin screens for JavaScript errors. Smoke level only.

## Out of Scope

- Everything about balances and withdrawals, including the withdrawals screen — specified separately, built on this shell.
- Public profile pages, wish pages, listings, and search. The blocked-content rule is built here; the pages that consume it are not.
- Soft-deleting users and wishes, and account deletion — still open work in `.scratch/contributions/issues/05`.
- An audit log of admin actions.
- Any role beyond admin, and any UI for granting admin.
- Editing or deleting contributions, wishes, or payments from the panel.
- Notifying a user that they were blocked, unblocked, or had their password changed.
- Admin-specific authentication hardening (separate login, two-factor, IP allowlist).
- Analytics, dashboards, or aggregate reporting.

## Further Notes

- Domain vocabulary per `CONTEXT.md`: Admin, Blocked, Contribution, Contributor, Wish, Owner, Toman. Copy and test names should not drift to synonyms — not "banned", "suspended", or "deactivated" for a blocked user, and not "transaction" for a payment.
- All money displayed is Toman (ADR-0002). Nothing in the admin panel talks to the gateway, so no Rial conversion arises here.
- There are no controllers in this application yet. This spec creates the first ones, so it also sets the convention that later efforts will follow.
