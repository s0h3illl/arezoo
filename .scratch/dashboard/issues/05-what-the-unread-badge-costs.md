# 05 — What the unread badge costs on every page

Type: grilling
Status: claimed
Blocked by: 04

## Question

[Charting](00-charting.md) chose one timestamp — `users.messages_seen_at` — over per-row `read_at`, and put the unread badge in the header menu. The menu is in `AppHeader`, which renders on **every page of the app**, so the count is a shared Inertia prop and its cost is paid on every request a signed-in user makes.

[What the inbox shows](04-what-the-inbox-shows.md) settles the row definition this counts, which is why that lands first.

Decide:

- **What the query actually is.** The count is not a column read: it is contributions joined to the reader's wishes, filtered to `paid`, non-null `message`, `visibility in (public, owner)`, and `settled_at > messages_seen_at`. Confirm the indexes that exist can answer it — `contributions` has `['wish_id', 'status']` and nothing on `settled_at`.
- **Whether it is deferred.** Inertia's deferred props would keep it off the critical path so no page render waits on it, at the cost of the badge appearing a beat late on every navigation. Decide which is worse.
- **Where it is shared from.** `HandleInertiaRequests`'s `share()` is the obvious home; confirm what already rides there (`auth.user`) and whether this joins it or sits beside it. Note that `edit-a-wish` deliberately narrowed the shared user prop, so widening the shared payload is a decision that effort has an opinion about.
- **Whether it is computed for guests.** It must not be. Say so explicitly — a shared prop that runs a join for every signed-out visitor to the home page is the failure mode this ticket exists to avoid.
- **When `messages_seen_at` is stamped.** On rendering `/dashboard/messages` is the obvious answer, but a `GET` that writes is a `GET` that writes: it breaks on a prefetch, and this app prefetches. Decide whether the stamp is a side effect of the render, a separate request the page fires, or something the user does.
- **What the stamp does to a paginated inbox.** If the page loads 7 of 40 and stamps immediately, 33 unread messages become read without being seen. Decide whether the stamp means "you opened the inbox" or "you saw these rows", and accept the consequence out loud.
- **What the badge shows past a point.** A number, a dot, or a capped «۹+». Persian digits, per the app's existing formatting.
