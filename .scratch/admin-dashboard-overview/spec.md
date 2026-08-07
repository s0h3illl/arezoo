# Spec: Admin dashboard overview

Status: ready-for-agent

## Problem Statement

The admin dashboard is a placeholder. An admin signs in, lands on the panel's front door, and is told "سلام دنیا 👋" and that the rest is coming soon. Every question an admin actually arrives with — how many people are on this platform, how much have they wished for, how much money has actually moved, what has been published in the last hour — is answerable today only by paging through a list built for a different purpose, or by opening a database client. The one screen positioned to answer those questions answers none of them.

There is a second, quieter problem the first one exposes. The panel treats admins as ordinary users: they are listed among the users, they are findable by search, and any admin can block another admin or set their password. Nobody decided that; it fell out of there being no rule. The moment the panel starts reporting a user count, that non-decision becomes a number on a screen that means something other than what it says.

## Solution

The dashboard becomes the panel's real landing screen: three platform totals across the top — users, wishes, and the money raised — and beneath them a table of the five newest wishes. The totals are a snapshot taken at most once a day, with the moment they were taken printed underneath them, so an admin can always tell the difference between "nothing happened today" and "this number is a day old". The wishes table is live on every visit, so the screen always has something current on it.

Alongside it, admins stop being users as far as the panel is concerned. They are absent from the user list, unfindable by search, and every admin route — detail, edit, block, set-password — answers a 404 for an admin target. Staff accounts are managed out of band, the same way admin status is granted out of band.

## User Stories

1. As an admin, I want the panel's front door to tell me something about the platform, so that signing in is worth doing when I have no specific task.
2. As an admin, I want to see how many users are on the platform, so that I can tell at a glance whether the app is growing.
3. As an admin, I want to see how many wishes have been published, so that I know how much content the platform holds.
4. As an admin, I want to see how much money has been raised in total, so that I know the scale of what the app is handling on people's behalf.
5. As an admin, I want the money total shown in Toman like everywhere else in the app, so that I never have to convert in my head.
6. As an admin, I want the money total to count only contributions whose money actually landed, so that the figure is money, not hope.
7. As an admin, I want the money total to be everything ever raised, for all time, so that it is one unambiguous number rather than a figure whose meaning shifts.
8. As an admin, I want the money total to be unaffected by withdrawals when they arrive, so that a number I have been watching for months does not silently change meaning.
9. As an admin, I want the user count to exclude staff accounts, so that it is a count of the people the platform serves.
10. As an admin, I want the user count to still include blocked users, so that blocking someone is not quietly a deletion.
11. As an admin, I want the user count to match the total on the users list exactly, so that two screens never tell me two different things about the same thing.
12. As an admin, I want the wish count to include wishes belonging to blocked users, so that the platform's size is not understated by moderation decisions.
13. As an admin, I want to know when the totals were calculated, so that I can tell a quiet day from a stale number.
14. As an admin, I want all three totals to come from the same instant, so that I am reading one coherent snapshot rather than three unrelated readings.
15. As an admin, I want the totals to be cheap to display, so that opening the panel does not get slower as the platform grows.
16. As an admin, I want the totals presented as plain readouts rather than links, so that no tile behaves differently from its neighbours.
17. As an admin, I want to see the five most recently published wishes, so that I know what is being put on the platform right now.
18. As an admin, I want the newest wish first, so that the most recent activity is what I read first.
19. As an admin, I want each wish row to show its title, so that I can see what was asked for.
20. As an admin, I want each wish row to name its owner, so that I can tell whose wish it is without a second lookup.
21. As an admin, I want each wish row to show its price in Toman, so that I can spot an implausible or abusive amount immediately.
22. As an admin, I want each wish row to show when it was published, so that I can tell whether the feed is minutes or weeks old.
23. As an admin, I want the wishes table to be live rather than cached, so that the dashboard always has one thing on it that is certainly current.
24. As an admin, I want wishes from blocked users to appear in the table, so that the content most likely to need review is not the content hidden from me.
25. As an admin, I want the wishes table to explain itself when there are no wishes yet, so that an empty platform does not look like a broken screen.
26. As an admin, I want the dashboard to read in Persian and RTL like the rest of the panel, so that it does not feel like a separate product.
27. As an admin, I want the dashboard to remain read-only, so that no click on a summary screen can change anything.
28. As an admin, I want other admins absent from the user list, so that the list is a list of the platform's users and nothing else.
29. As an admin, I want searching the user list to never return an admin, so that hiding staff accounts is not defeated by the one tool for finding people.
30. As an admin, I want an admin's detail page to answer as though it does not exist, so that hiding staff accounts is a real rule and not a cosmetic one.
31. As an admin, I want blocking an admin to be impossible, so that a compromised or careless admin session cannot lock the platform's operators out.
32. As an admin, I want setting an admin's password to be impossible, so that the panel cannot be used to take over another operator's account.
33. As an owner of the system, I want the rule that hides admins to be enforced in one place, so that a screen added later cannot forget it.
34. As an owner of the system, I want staff accounts managed out of band, so that panel access and the ability to change panel access are not the same power.
35. As a user who is not an admin, I want the dashboard to be indistinguishable from a page that does not exist, so that the panel's existence is not advertised.
36. As a guest, I want the dashboard to answer the same way, so that no admin URL confirms itself to someone who guessed it.

## Implementation Decisions

- The dashboard controller gains props; it stays a single read-only screen with no writes, no filters, and no query parameters.
- **Users total**: a count of users that are not admins. Blocked users are counted — blocking is a moderation decision, not a deletion, consistent with the existing rule that blocking never touches a user's money.
- **Wishes total**: a count of every wish. No exclusions: not blocked owners, not any notion of fulfilment (wishes have no such state, and contributions are uncapped per ADR-0004).
- **Raised total**: the sum of the amounts of paid contributions, reusing the existing paid scope rather than a second definition of "money that landed". Pending contributions are excluded, since a failed attempt deletes its contribution outright (ADR-0001) and a pending row is money that may still evaporate.
- The raised total is lifetime gross and never decreases. When withdrawals land, they do not reduce it. This is a deliberate choice of "money ever raised" over "money currently held", so the tile's meaning survives the arrival of the Withdrawal concept without redefinition. The Persian label names the collected amount rather than a balance, so the two are not confusable on screen.
- The three totals are computed together and stored as **one cache entry**, holding the three numbers plus the moment they were taken. One entry, not three: three entries would expire at three different moments, and a single "last updated" line beneath them would then be false for at least two of the numbers.
- The snapshot's time-to-live is **24 hours**, with no invalidation on write. Nothing in the app's write paths needs to know the dashboard exists.
- Because a day-long snapshot can be a day wrong, the moment it was taken is **displayed** beneath the tiles, formatted with the app's existing Persian moment formatting. The tiles are only permitted to be stale because the screen admits when they were taken.
- The moment is stored in the cache entry as a **string, not a date object**. The application's cache configuration disables unserialisation of arbitrary classes, so caching a date instance would fail; a string is also what the frontend's moment formatting already consumes.
- The cache store is the database store, which does not support cache tags. Invalidation, if ever needed, is by key.
- **Latest wishes**: the five newest, ordered by publication time descending with the identifier as a tiebreak, since bulk-created wishes can share a timestamp and would otherwise order unpredictably. Not cached. Not paginated. No link to a fuller list, because no wishes section exists and this spec does not build one.
- Each wish row carries title, owner name, price, and publication time. It deliberately does **not** carry the amount raised for that wish: that figure is a per-wish aggregate and would either be an N+1 across the table or an aggregate join added for a column nobody asked for.
- The owner is eager-loaded with the wishes so the table is two queries, not six.
- The wish rows are serialised through a dedicated admin resource for wishes, following the existing convention that admin screens read models through a narrow, deliberate shape rather than handing the model to the frontend. A corresponding frontend type for a wish is added to the shared types, since a wish is an original domain type; the dashboard's own props shape stays local to the dashboard page.
- None of the three tiles are links. Two of them could link somewhere and one could not, and a row where one tile silently does not respond to a click is worse than a row where none do. The panel's navigation shell is already how an admin reaches the sections.
- **Admins become invisible to the users section.** The rule is enforced once, at route-model binding: the user route parameter resolves only non-admin users and 404s otherwise. This covers the detail view, the edit and block route, and the set-password route in one place, so no controller action can forget it. The user list applies the same exclusion to its query.
- The consequence is accepted deliberately: no admin can be blocked, renamed, or given a new password through the panel by any means, including a crafted request. Staff accounts are managed where admin status is granted — out of band, in a seeder or the database. This mirrors the existing decision that admin status is ungrantable through the app.
- The route parameter binding is global rather than scoped to the admin route group. The user route parameter appears only in admin routes today; a non-admin route that binds it later would inherit the rule, which is a known and intended property of putting it there.
- The dashboard's placeholder heading and "coming soon" copy are removed. The empty state for the wishes table follows the pattern already used by the contributions list rather than inventing a second one.
- **`CONTEXT.md` is amended**: the Admin entry currently reads that an admin can see every user, which stops being true — it should say every user other than an admin, and record that admins are not themselves managed from the panel. A term for the lifetime gross raised total is added, defined against Balance so the two are not confused: Balance is what an owner may withdraw, and the raised total is what the platform has ever taken in.
- **An ADR is written** for making admins invisible to the users section. It qualifies on all three counts: it is hard to reverse, it is surprising to a future reader who tries to block an account and gets a 404, and it is a real trade-off — one rule enforced in one place, paid for with all panel control over staff accounts.

## Testing Decisions

- A good test here asserts what an admin sees, at the HTTP boundary: which props the dashboard hands the frontend, which numbers they hold, which rows the table holds and in what order, and what status code an admin-target route answers. It never asserts a cache key, the shape of a cache entry, or how a total is computed.
- **One seam**: Pest feature tests over the admin HTTP routes, asserting Inertia props, in the style already established by the existing admin feature tests. No unit seam is added for the totals or the snapshot — a stats class tested directly would be a second seam over behaviour the first one already covers, and its assertions would name implementation details the HTTP seam does not need to know.
- Caching is tested through its consequence rather than its mechanism: read the dashboard, create users, wishes, and paid contributions, read it again and assert the totals and the recorded moment are unchanged; then travel past the day-long window and assert all three refresh together. That the tiles move as one snapshot is itself an assertion, since it is the property the single cache entry exists to guarantee.
- Tests run against the array cache store, which persists for the duration of a single test. Any test asserting a total after inserting rows must account for this — either by flushing first or by making the caching itself the thing under assertion.
- Each total is tested against the rule that defines it, not just for being a number: an admin user is created and asserted absent from the user total; a blocked user is asserted present in it; a wish belonging to a blocked user is asserted present in the wish total and in the table; a pending contribution is asserted absent from the raised total while a paid one is present.
- The wishes table is tested for its cap, its ordering, the fields each row carries, and its empty state.
- **Existing tests that must change**, all in the users list tests: the test asserting an admin sees every user (its body counts the acting admin, and its name becomes a false statement about the domain); the pagination test, whose totals include the acting admin; and the test asserting the serialised shape of a row, which creates only the acting admin and would be left with no row to assert against.
- New access tests follow the convention already set for the panel: an admin target answers 404 on the detail, update, block, and set-password routes, asserted for each route rather than for the binding, since the binding is the mechanism and the 404 is the behaviour.
- The dashboard keeps its existing access tests unchanged: a signed-in non-admin and a guest each get a 404 rather than a 403 or a login redirect.
- Browser smoke tests stay at smoke level, extending the dashboard's existing one to see the new Persian copy renders without JavaScript errors. The existing admin browser tests all act on non-admin users and need no changes.

## Out of Scope

- An admin wishes section — a list, detail view, search, or pagination for wishes. The dashboard's five rows link nowhere, and the wishes total has nowhere to click through to. This is the most likely follow-up.
- Any manual refresh control for the totals, and any invalidation of the snapshot on write.
- Per-wish figures on the dashboard: amount raised, contributor counts, funding progress.
- Any total involving balances, held or available money, or withdrawals — including the platform's outstanding liability, which is the number the raised total is deliberately not.
- Charts, trends, time series, comparisons against a previous period, or any breakdown by date range.
- Any tile or table for payments, including failed gateway attempts, despite the payments section existing.
- Moderation actions taken from the dashboard. It stays read-only.
- A UI for granting or revoking admin status, and any second admin role. Unchanged: admin is granted out of band.
- An audit log of admin actions, still out of scope as it was for the panel itself.
- Any change to what a non-admin user can see anywhere in the app.

## Further Notes

- The original admin panel spec lists "Analytics, dashboards, or aggregate reporting" as out of scope. This spec deliberately reverses that, narrowly: three platform totals and a five-row feed, no trends and no breakdowns. The wider exclusion still stands.
- Domain vocabulary per `CONTEXT.md`: Admin, Blocked, Contribution, Contributor, Wish, Owner, Toman, Balance. Copy and test names should not drift to synonyms — not "donation" for a contribution, not "banned" or "suspended" for a blocked user, and not "balance", "wallet", or "funds" for the raised total, which is a different thing from a Balance and must not be labelled like one.
- All money displayed is Toman (ADR-0002). Nothing here talks to the gateway, so no Rial conversion arises.
- Hiding admins from the users section is a change to an already-shipped feature, not only an addition. It should be reviewed as such: the users list, its search, and its pagination all change behaviour, and three of their tests change with them.
- The dashboard is the first screen in the panel to read across users, wishes, and contributions at once. Whatever shape the totals take here sets the convention for the withdrawals figures that the balance spec will eventually want on this screen.
