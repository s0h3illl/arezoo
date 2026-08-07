# 02 — Three platform totals on the dashboard

**What to build:** An admin opening the panel's front door sees three totals across the top instead of placeholder copy: how many users are on the platform, how many wishes have been published, and how much money has been raised in Toman. Beneath them sits the moment the three figures were calculated, so an admin can always tell a quiet day from a day-old number.

The totals are a snapshot taken at most once a day. All three come from the same instant and are stored together, so the moment printed beneath them is true of every one of them — not of whichever was recalculated last. Nothing in the app's write paths needs to know the dashboard exists; the snapshot simply ages out.

What each total means:

- **Users** — users who are not admins. Blocked users are counted: blocking is a moderation decision, not a deletion, consistent with blocking never touching a user's money. This figure must equal the total the users list reports, which is what ticket 01 makes true.
- **Wishes** — every wish, with no exclusions. Wishes belonging to blocked users are counted, so moderation does not understate the platform's size.
- **Raised** — the sum of the amounts of contributions whose money actually landed, reusing the existing paid rule rather than defining "money that landed" a second time. Pending contributions are excluded: a failed attempt deletes its contribution outright (ADR-0001), so a pending row is money that may still evaporate. The figure is lifetime gross and never decreases — when withdrawals arrive they do not reduce it. It is deliberately *not* the money the platform currently holds, and its label must not read like a Balance.

The tiles are plain readouts. None of them link anywhere: two could and one could not, and a row where a single tile silently ignores a click is worse than a row where none respond.

**Blocked by:** 01 — Admins are invisible to the users section. The users total counts non-admins, and until 01 lands it would disagree with the users list, which is the exact defect this tile is supposed to avoid.

**Status:** ready-for-agent

- [ ] An admin sees three totals on the dashboard: users, wishes, and money raised.
- [ ] The money total is shown in Toman using the app's existing money formatting.
- [ ] The users total excludes admins and includes blocked users, and equals the total the users list reports.
- [ ] The wishes total counts every wish, including those belonging to blocked users.
- [ ] The raised total counts paid contributions only; a pending contribution does not move it.
- [ ] The moment the totals were calculated is displayed beneath them, using the app's existing Persian moment formatting.
- [ ] Reading the dashboard, then creating users, wishes, and paid contributions, then reading it again leaves all three totals and the displayed moment unchanged.
- [ ] After the day-long window passes, all three totals and the moment refresh together — they move as one snapshot, which is the property storing them together exists to guarantee.
- [ ] The recorded moment survives being cached: the application disables unserialisation of arbitrary classes, so it is stored as a string rather than a date object.
- [ ] None of the tiles are links.
- [ ] The placeholder heading copy and the "سلام دنیا" card are gone.
- [ ] The dashboard remains read-only, with no filters and no query parameters.
- [ ] Existing dashboard access tests still pass: a signed-in non-admin and a guest each get a 404.
- [ ] The dashboard browser smoke test sees the new Persian copy render without JavaScript errors.
- [ ] `CONTEXT.md` gains a term for the lifetime gross raised total, defined against Balance so the two are not confused — a Balance is what an owner may withdraw; this is what the platform has ever taken in.
- [ ] Tests assert what an admin sees at the HTTP boundary. No test names a cache key or asserts the shape of a cache entry.
