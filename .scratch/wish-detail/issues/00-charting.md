# 00 — The destination and the shape of the page

Type: grilling
Status: resolved
Blocked by: none

## Question

`wish-detail.dc.html` arrived as a finished-looking design for a page the app does not have. Before anything can be charted: what is this effort actually delivering, and which parts of the design survive contact with the domain?

## Answer

**Destination.** A ready-for-agent `.scratch/wish-detail/spec.md` plus numbered issues, in the shape of `.scratch/edit-a-wish/spec.md`. Not the built page — the map plans, someone else builds.

**The contribute flow is a separate effort.** The design's §4.2 modal is the heart of the page and has no server behind it: there is no gateway in `config/services.php`, no controller that creates a `Contribution`, and nothing outside tests that calls `markPaid()` or `abandon()`. Contributions reach the database only through the factory and seeder. Specifying the page and specifying how the platform takes money are two jobs, and only the first is finishable here. This spec records the contract the CTA will fill; `.scratch/contribute/` builds it.

**The CTA is omitted, not stubbed.** The funding card ships with the summary row, progress bar and funded notice, and no «منم کمک می‌کنم» button at all. A disabled button announcing «به‌زودی» would put a promise on the app's central page; a live button in front of nothing would be a dead control.

**No breadcrumb, and no lists.** The design's §1.2 breadcrumb is cut entirely — the header's «برگشت به پروفایل» is the way back up. Its «از لیست «{list.title}»» chip goes with it, and so does §7's private-list 404 and its share token. `CONTEXT.md` is unambiguous that a Profile holds every wish a user has published; there is no grouping between the two, and inventing one is a feature in its own right rather than a detail of this page.

**Route: `GET /wishes/{wish}`, named `wishes.show`,** binding the integer id. It sits with the `wishes.store` / `wishes.update` / `wishes.destroy` trio already in `routes/web.php`, which bind the same way. The design's `/w/:wishId` implies a slug column that does not exist. Ids being enumerable hides nothing that a public profile does not already publish.

**Contributor rows are governed by Visibility, not by a page flag.** The design's page-level `showAmounts` and its per-row typed `anon` name are replaced by the domain's per-contribution `visibility`. Every paid contribution renders as a row, so the list and the progress bar agree; where the reader may not see the contributor, the row renders as the design's anonymous variant — slate avatar with «؟», name «ناشناس», amount withheld. `hidden` masks for everyone including the owner; `owner` masks for everyone but the owner. Omitting masked rows instead was rejected: a wish funded entirely by hidden contributions would read as untouched while its bar sat full.

**The owner controls are already built.** `EditWishDialog.vue` and `DeleteWishDialog.vue` ship on the profile card and do what §5 describes; the page renders the same two components, restyled to the design's 36px icon buttons. Deleting from here redirects to the profile, because the page it happened on no longer exists. The design's refund warning and typed confirmation are dropped: ADR-0003 means a delete soft-deletes, every contribution survives untouched, and there is no refund to warn about. The price-change callout and progress-delta line of §5 are a change to a shared component — wanted on the grid as much as here — so they are not this effort's to make.

**The page is reached from the card's cover and title.** Both become links to `wishes.show`; the footer's edit, share and delete controls stay buttons outside the anchor. Making the whole card a link would nest three buttons and two dialogs inside it.

**Over-funding follows the app, not the design.** `formatShare` in `resources/js/lib/format.ts` already renders «۱۴۰٪» and says in a comment that it reads as honestly as 1.4, citing ADR-0004; `WishCard` clamps only the bar width. The design's `pct = min(round(…), 100)` would make one wish report two different figures on two pages. The bar clamps; the number does not.
