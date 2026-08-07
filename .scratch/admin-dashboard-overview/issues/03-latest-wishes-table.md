# 03 — Latest wishes table

**What to build:** Beneath the totals, an admin sees the five most recently published wishes — what was asked for, whose wish it is, its price in Toman, and when it was published. Newest first. This is the one part of the dashboard that is read fresh on every visit, so the screen always has something on it that is certainly current, however old the totals above it are.

Wishes belonging to blocked users appear here. The content most likely to need review is not the content hidden from the person doing the reviewing, and blocking bars a user from the app rather than confiscating what they published.

When there are no wishes at all, the table explains itself rather than rendering as a blank frame, following the pattern the contributions list already uses rather than inventing a second one.

The table is a glance, not a list: five rows, no pagination, no filters, and no link to a fuller list — there is no wishes section to link to, and this ticket does not build one. Rows link nowhere.

Deliberately absent: how much each wish has raised. That figure is a per-wish aggregate and would cost either a query per row or an aggregate join, for a column the screen does not need.

**Blocked by:** 02 — Three platform totals on the dashboard. Same screen and same controller; 02 establishes the props the table extends.

**Status:** ready-for-agent

- [ ] An admin sees the five most recently published wishes beneath the totals.
- [ ] With more than five wishes, exactly five are shown — the newest.
- [ ] The newest wish is first, and wishes created within the same moment still order predictably rather than arbitrarily.
- [ ] Each row shows the wish's title, its owner's name, its price in Toman, and when it was published, using the app's existing money and moment formatting.
- [ ] A wish belonging to a blocked user appears in the table.
- [ ] With no wishes on the platform, the table is replaced by copy explaining why it is empty, in the style already used by the contributions list.
- [ ] The table reflects a wish created since the totals were last calculated — it is read fresh on every visit, not part of the cached snapshot.
- [ ] Listing the table does not run a query per row for the owner.
- [ ] Rows are not links, and there is no pagination or "see all" control.
- [ ] The wish rows are serialised through a narrow, deliberate shape rather than handing the model to the frontend, following the convention the other admin screens already set.
- [ ] The frontend type for a wish lives with the other original domain types; the dashboard's own props shape stays local to the dashboard page.
- [ ] The dashboard browser smoke test sees the table's Persian copy render without JavaScript errors.
- [ ] Existing dashboard access tests still pass: a signed-in non-admin and a guest each get a 404.
