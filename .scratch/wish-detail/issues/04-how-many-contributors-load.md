# 04 — How many contributors the page loads

Type: grilling
Status: resolved
Blocked by: 03

## Question

The design's contributor card lists rows with no ceiling on them and a count pill above. A wish that goes round a family gets six; a wish that goes round the internet gets six hundred, and every one of them ships inside the `data-page` attribute of the HTML document.

- **How many rows arrive with the page?** All of them, a first page, or a first page plus a way to ask for the rest. The profile grid answers this with `Inertia::scroll()` and `<InfiniteScroll>` over a 12-per-page paginator — precedent that already exists in this codebase and that a reader has already met once.
- **If it pages, what is the page size**, and does the design's card need any affordance the mockup does not draw — a «بیشتر» control, a scroll region with its own height, or nothing because infinite scroll handles it.
- **The count pill.** «{n} نفر» counts people, not contributions — `Wish::contributorCount()` already distinguishes them, counting distinct contributors so someone who gives twice counts once. Confirm the pill uses that, and settle what it means when the list below it is paged: the pill counts everyone, the visible rows are fewer, and those two numbers disagreeing on screen needs to be deliberate.
- **Someone who gave twice.** They are one person in the count and two rows in the list. Is that right, or does the list group by contributor and sum? Grouping changes what a row's date and message even mean.
- **The empty state.** The design replaces the rows with «هنوز کسی کمک نکرده — اولین نفر باش» and keeps the pill at «۰ نفر». That copy invites an action the page cannot currently offer, since [the charting ticket](00-charting.md) omitted the CTA until the contribute effort lands. Decide what it says in the meantime.

## Answer

**Rows arrive as a first page plus `<InfiniteScroll>`, matching the profile grid's precedent exactly** — `Inertia::scroll()` wrapping a paginator, `<InfiniteScroll data="contributions" items-element="...">` fetching the rest as the reader scrolls. **The page size is 7** (per the user, overriding the profile grid's 12 — a contributor list is denser text than a card grid, and this is the mockup's own row count guess made deliberate). No «بیشتر» button or scroll-region-with-its-own-height: the design draws neither, and the profile grid ships none either — the component's own scroll trigger is the whole affordance, nothing extra to add.

**The count pill does not use `Wish::contributorCount()`.** After discussion, it counts contribution rows instead — the same total the paginator carries (`contributions.meta.total`, read from the same paginated resource the list renders, so the two numbers can never disagree — the same trick the profile grid's own stat pill uses against `wishes.meta.total`). Because a row-count is not a person-count, the pill's label changes from «نفر» (person) to **«مشارکت»** (contribution) so the copy stays true when someone gave twice: «۲ مشارکت» from one repeat giver reads correctly, where «۲ نفر» would not have. `Wish::contributorCount()` is not used on this page.

**Someone who gave twice is two rows, not one grouped-and-summed row** — already settled by [What a contributor row shows](03-what-a-contributor-row-shows.md), which fixed a row to `Wish::paidContributions()` (one row per paid contribution, no grouping). The pill decision above is consistent with that: both the pill and the list now count the same thing (contributions, not people), so there is no "these two numbers disagree and that's deliberate" case left to design for — they agree by construction.

**The empty state drops the CTA half of the design's copy.** No contribute action exists on this page yet ([00 — charting](00-charting.md)), so inviting one is wrong regardless of wording. Text becomes «هنوز کسی کمک نکرده» (full stop, no «اولین نفر باش»), pill at «۰ مشارکت» — matching how `Profile.vue`'s non-owner empty state states a fact with no action drawn under it.
