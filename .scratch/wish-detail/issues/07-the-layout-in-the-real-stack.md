# 07 — The layout in the real stack

Type: prototype
Status: resolved
Blocked by: 03, 04

## Question

The design is one static HTML file of inline styles, written outside this app: no Tailwind, no Vazirmatn, no `AppLayout`, and a contribute CTA that [the charting ticket](00-charting.md) removed from the funding card it anchors. Whether the page still reads well once those four things are true is a question to look at rather than argue about.

Build a throwaway prototype — a Vue page in this app's stack, real components where they exist, fake props — and use it to answer:

- **Does the funding card survive losing its CTA?** It was designed around a full-width button at the bottom; without one it is a summary row, a bar and a meta line, and it may want to merge into the wish card rather than float as a card of its own.
- **The two-column grid** at `repeat(auto-fit, minmax(320px, 1fr))`, in Tailwind, in RTL. Confirm the wish card lands on the correct side and that the collapse to one column at ~700px puts the funding column *below* the wish rather than above it.
- **360px.** The design's ninth section asks for it explicitly. Long titles and a long description are the ones that break.
- **The photo.** `clamp(220px,32vw,320px)` tall and full-bleed here, against the card's `aspect-[4/3]`, using the same `wish-cover-placeholder` stripe when there is none.
- **The contributor card at both extremes** — no rows, and enough rows to hit whatever [How many contributors the page loads](04-how-many-contributors-load.md) settled — with a masked row beside a named one so the anonymous variant is judged in context rather than alone.

The prototype is thrown away. What survives is the decision about the layout, recorded here, and any screenshots worth linking.

## Prototype

**Captured and removed.** Lives on the throwaway branch **`prototype/wish-detail-layout`** (commit `f69ad22`), off the working branch. Nothing prototype-shaped remains on `research/relative-time`. To look at it again: `git checkout prototype/wish-detail-layout`, `composer run dev`, then `/prototype/wish-detail`.

What it was — throwaway, local-only, `/prototype/wish-detail`.

- `routes/prototype.php` — fake props, `require`d from `web.php` only under `app()->environment('local')`.
- `resources/js/pages/prototype/wish-detail/` — `Index.vue` (switcher host), `VariantA/B/C.vue`, `Switcher.vue`, `ContributorRow.vue`.

Real stack throughout: `AppLayout`, Vazirmatn, Tailwind, reka-ui `ProgressRoot`, the app's own `formatToman` / `formatShare` / `shareOf`, and the shipped `wish-cover-placeholder` stripe. No CTA anywhere, no breadcrumb. «برگشت به پروفایل» is pushed into the real header via `setHeaderNav()` — which turns out to be the existing mechanism the charting ticket's "the header's «برگشت به پروفایل»" needs, rather than a new slot on `AppLayout`.

**Variants** (`?variant=`, or ← → keys):

- **A — طراحی اصلی، بدون CTA.** The design as drawn, minus the button. Two columns, three cards: wish left, funding card and contributor card stacked right.
- **B — پول داخل کارت آرزو.** The funding card dissolved into the wish card as a slate-50 money block under the title. The right column holds only the contributor list.
- **C — تک‌ستونی، بدون گرید.** No grid at all. One 720px column: taller hero photo, title, a full-width money strip bounded by rules rather than a card, description, then the list full width.

**Cases** (`?case=`, buttons on the switcher): `default`, `empty` (no contributors), `funded` (over-funded — «۱۴۰٪» with a clamped bar), `long` (long title and description), `nophoto` (placeholder stripe), `owner` (edit/delete buttons shown).

Contributor rows carry a named row, an anonymous row and a deleted row adjacent, so the three states are judged against each other. Every row shows its amount, per the domain change in [The server shape for the wish page](05-the-server-shape.md).

Verified: `bun run types:check` and `bun run build` both clean; all three variants and the cases return 200.

## Answer

**Variant B wins: the funding card is dissolved into the wish card.**

**The funding card does not survive losing its CTA.** That is the ticket's central question and the answer is no. It was a *card* because it had a full-width button to anchor; strip the button and what remains — a summary row, a bar, a meta line — is not enough to justify a border, a 24px radius and `clamp(20px,4vw,28px)` of padding floating as a sibling of the wish. In B it becomes a `rounded-2xl bg-slate-50` block **inside** the wish card, sitting between the title row and the description. Money reads as a fact about the wish rather than as a separate object beside it.

**The two-column grid survives unchanged.** `repeat(auto-fit, minmax(320px, 1fr))`, gap `clamp(18px,3vw,28px)`, `items-start`, container max-width 1040px, gutter `clamp(16px,4vw,32px)`. What changes is what fills the second column: the contributor card **alone**, no longer a funding card stacked above it. The right column is now shorter than the wish column on desktop, which `items-start` already handles.

The wish card is first in DOM, so in RTL it takes the start (right) side and the contributor card takes the left; on the collapse to one column the wish stays above the contributors, which is the order the ticket asked to confirm.

**The money block, precisely:**

- Received amount and «از {price}» on one line — the price demoted to `12.5px` slate-400 beside the emerald-700 figure, rather than exiled to a «هدف:» meta row of its own.
- Percentage at the row end, same `text-xl font-black` weight as the amount, so the two figures read as a pair rather than the percentage dominating at 30px as the design had it.
- `h-2.5` slate-200 track, emerald-500 fill. **The bar clamps; the number does not** — over-funding shows «۱۴۰٪» with a full bar, per [the charting ticket](00-charting.md) and `formatShare`'s own comment.
- One meta line below: «{remaining} تومان مونده», or «تکمیل شد» once funded.

**Everything else carried over as drawn.** Photo `clamp(220px,32vw,320px)`, full-bleed, `object-cover`, falling back to the shipped `wish-cover-placeholder` stripe. «برآورده شد!» badge absolutely positioned at top/inline-start, only at or above 100%. Contributor card head with the «{n} مشارکت» pill, `border-top` row dividers, «هنوز کسی کمک نکرده» when empty.

**One thing the prototype settled that was not on the ticket's list.** «برگشت به پروفایل» needs no new slot on `AppLayout`: `setHeaderNav()` already exists for exactly this — a page naming its own header links, emptied again on unmount — and the real header renders them. [The charting ticket](00-charting.md) said the header carries the way back up without saying how; this is how, and nothing shared has to change to allow it.

**Not separately confirmed:** the design's §9 asks for 360px explicitly, and the `long` case exists in the prototype to stress it, but no verdict on that width was recorded here. It carries forward as an acceptance criterion for [Write the spec](08-write-the-spec.md) rather than as a settled observation.
