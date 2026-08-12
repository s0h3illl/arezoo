# 03 — Adding a wish

**What to build:** The first way anything in this app creates a wish. Until now the `wishes` table has been populated only by its factory: every wish on the platform was put there by a seeder.

The owner's «آرزوی جدید» button opens a modal over their profile — no route change. It asks for a title and a price, and offers a description, a product link, and a picture to anyone who wants to give them. Title and price are the only required fields, so adding a wish stays a quick act.

**The price is required even though the design called it optional.** The card's progress bar has no meaning without a target, and a priceless wish would need a second card state that nothing else in the app wants. In the other direction, `description` and `purchase_link` **become nullable columns** to match the design's intent that both are optional; the card already omits the description row when it is absent.

The picture is an optional upload, validated as an image with a size cap and stored on the public disk exactly as `WishFactory` already does. This is the first time the app has served an uploaded file: **`php artisan storage:link` is required**, and without it every cover would 404.

The modal is a reka-ui dialog, which supplies the focus trap, Escape handling, backdrop dismissal, `aria-modal` and body scroll lock rather than any of them being hand-rolled.

Submission is a plain Inertia form post with `forceFormData`, and the server's response is the truth — no optimistic insertion. A rejected submission leaves the modal open with its errors inline and everything the user typed still there. A successful one closes and resets it, and the new wish is simply present in the refreshed props.

**Blocked by:** 02 — The profile page and its wish grid.

**Status:** ready-for-agent

- [ ] `wishes.description` and `wishes.purchase_link` become nullable, edited into the existing wishes migration
- [ ] The owner's header button opens a modal over the profile without changing route
- [ ] The modal asks for title, description, product link, price, and a picture
- [ ] Title and price are required; description, link and picture are optional
- [ ] A missing title or price is rejected with a Persian message shown against the field
- [ ] An invalid product link and a non-image or oversized file are each rejected with their own message
- [ ] A rejected submission leaves the modal open with every typed value intact
- [ ] A successful submission closes the modal, resets it, and the new wish is on the page
- [ ] The new wish belongs to the profile's owner, whatever the request claims
- [ ] An uploaded picture is stored on the public disk and renders as the card's cover
- [ ] `php artisan storage:link` is documented as required, and a stored thumbnail is reachable over HTTP
- [ ] A signed-in visitor and a guest are both refused the store route
- [ ] The modal closes on Escape, on the close button, and on a click outside the panel, and a click inside it does not close it
- [ ] Focus is trapped in the modal while open, the first field takes focus on open, and focus returns to the trigger on close
- [ ] The page behind the modal does not scroll while it is open
- [ ] Pint clean, larastan clean, `bun run types:check` clean
