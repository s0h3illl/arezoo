# 02 — The profile page and its wish grid

**What to build:** The page behind `/u/{username}` — whose profile it is, and every wish they have published, as a grid of cards showing how close each one is to being paid for.

The header carries the avatar, the name, the `@username`, the bio when there is one, and a single chip counting the wishes. That chip counts what the grid holds, so the two can never disagree. There is no total-raised chip: it would publish a person's incoming money to anyone holding the link, and it reads as a Balance, which it is not.

Each card shows its cover, its title, its description when it has one, and a progress bar against the price. The raised figure counts paid contributions only — the same definition `Wish::receivedTotal()` already uses — and is fetched as **one aggregate for the whole grid**, never a query per card.

Contributions are uncapped (ADR-0004), so a wish can hold more than its price. The **bar clamps at full and the numbers do not**: an over-funded wish shows a complete bar above «۷۰۰,۰۰۰ از ۵۰۰,۰۰۰» and «۱۴۰٪». The bar is a gauge; the figures are the record.

Nothing on the card is a link. `purchase_link` does not appear on this page at all — it belongs to the wish detail page, which is the next piece of work.

The grid loads more as the reader scrolls, served with `Inertia::scroll()` over a paginator. Newest wish first, with the id as a tiebreak so wishes sharing a timestamp still order predictably.

The owner sees an «آرزوی جدید» button in the header; a visitor sees no button and no delete controls. Owner-ness is derived server-side, not a switch. Empty grids are two different screens: an owner is invited to add their first wish, a visitor is simply told there are none.

The modal behind the button is 03; the delete control on each card is 04; the share button is 05. This ticket renders the button and the card footer, with those actions not yet wired.

**Blocked by:** 01 — Username and the profile route.

**Status:** done

- [x] `/u/{username}` renders a page showing the user's avatar, name, username, and bio when set
- [x] A user without an avatar gets a placeholder rather than a broken image, using reka-ui's avatar fallback
- [x] A single chip shows the wish count in fa-IR digits, and it matches the number of wishes in the grid
- [x] Each card shows the wish's cover, title, description when present, and its price in Toman
- [x] A wish with no thumbnail renders the striped placeholder cover
- [x] Each card shows a progress bar with the amount raised against the price, using reka-ui's progress primitive
- [x] Only paid contributions count towards the raised figure; pending ones are ignored
- [x] A wish that has received more than its price shows a full bar, the true amount, and a percentage above 100
- [x] Rendering the grid does not run a query per card for the raised figure
- [x] Wishes are ordered newest first, and wishes sharing a timestamp order predictably
- [x] The grid loads further wishes as the reader scrolls, via `Inertia::scroll()` and `<InfiniteScroll>`
- [x] Nothing on a card is a link, and `purchase_link` appears nowhere on the page
- [x] The owner sees the «آرزوی جدید» button; a signed-in visitor and a guest do not
- [x] An owner with no wishes sees copy inviting them to add one; a visitor sees copy stating there are none
- [x] `AppHeader`'s account button points at the signed-in user's own profile, and its placeholder comment is gone
- [x] The wish shape is serialised through a dedicated resource rather than handing the model to the frontend
- [x] A browser smoke test loads a profile and sees its Persian copy render without JavaScript errors
- [x] Pint clean, larastan clean, `bun run types:check` clean
