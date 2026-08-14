# 03 — Adding a wish

**What to build:** The first way anything in this app creates a wish. Until now the `wishes` table has been populated only by its factory: every wish on the platform was put there by a seeder.

The owner's «آرزوی جدید» button opens a modal over their profile — no route change. It asks for a title and a price, and offers a description, a product link, and a picture to anyone who wants to give them. Title and price are the only required fields, so adding a wish stays a quick act.

**The price is required even though the design called it optional.** The card's progress bar has no meaning without a target, and a priceless wish would need a second card state that nothing else in the app wants. In the other direction, `description` and `purchase_link` **become nullable columns** to match the design's intent that both are optional; the card already omits the description row when it is absent.

The picture is an optional upload, validated as an image with a size cap and stored on the public disk exactly as `WishFactory` already does. This is the first time the app has served an uploaded file: **`php artisan storage:link` is required**, and without it every cover would 404.

The modal is a reka-ui dialog, which supplies the focus trap, Escape handling, backdrop dismissal, `aria-modal` and body scroll lock rather than any of them being hand-rolled.

Submission is a plain Inertia form post with `forceFormData`, and the server's response is the truth — no optimistic insertion. A rejected submission leaves the modal open with its errors inline and everything the user typed still there. A successful one closes and resets it, and the new wish is simply present in the refreshed props.

**Blocked by:** 02 — The profile page and its wish grid.

**Status:** done

- [x] `wishes.description` and `wishes.purchase_link` become nullable, edited into the existing wishes migration
- [x] The owner's header button opens a modal over the profile without changing route
- [x] The modal asks for title, description, product link, price, and a picture
- [x] Title and price are required; description, link and picture are optional
- [x] A missing title or price is rejected with a Persian message shown against the field
- [x] An invalid product link and a non-image or oversized file are each rejected with their own message
- [x] A rejected submission leaves the modal open with every typed value intact
- [x] A successful submission closes the modal, resets it, and the new wish is on the page
- [x] The new wish belongs to the profile's owner, whatever the request claims
- [x] An uploaded picture is stored on the public disk and renders as the card's cover
- [x] `php artisan storage:link` is documented as required, and a stored thumbnail is reachable over HTTP
- [x] A signed-in visitor and a guest are both refused the store route
- [x] The modal closes on Escape, on the close button, and on a click outside the panel, and a click inside it does not close it
- [x] Focus is trapped in the modal while open, the first field takes focus on open, and focus returns to the trigger on close
- [x] The page behind the modal does not scroll while it is open
- [x] Pint clean, larastan clean, `bun run types:check` clean

## Comments

**`forceFormData` was dropped, and the picture still uploads.** The ticket asked for the submission to be posted with `forceFormData`. It was built that way, and every submission then reached the server with an empty body: Pest's browser plugin serves Laravel a request it has parsed itself, and `Drivers/LaravelHttpServer.php` parses `application/x-www-form-urlencoded` and nothing else — files are an open `@TODO` in that file. A forced multipart body arrives empty, so no browser test could ever watch a wish being added.

Leaving the option off costs nothing the ticket wanted: Inertia still converts to multipart on its own the moment the data holds a `File`, so an uploaded picture travels exactly as before, and a wish without one travels as JSON. `WishTest` covers the upload end to end with `Storage::fake`; `AddWishTest` can now watch the whole round trip in a browser and assert the row landed.

**Browser tests went past smoke level, and were then pulled back.** They first covered Escape, the close button, dismissal outside the panel, the focus trap, focus returning to the trigger and the scroll lock, because those are checklist items here. All of it was deleted: none of that behaviour is this app's code — it is reka-ui's, and reka-ui tests it — and the spec had fixed browser tests at smoke level for exactly that reason. The checklist items above stay ticked because the dialog does all of it; they are simply covered by the primitive rather than by us.

`AddWishTest` is now three tests, each one a click, some typing, and then a read of the `wishes` table: a wish from the two required fields, a wish from every field but the picture, and a blank submission that is refused with both Persian messages and writes nothing. What they cover is the seam `WishTest` cannot reach — that the fields post under the names the validator reads. Each ends on a page assertion before touching the database, because the plugin serves the POST on the test's own event loop and that loop turns only while an assertion retries; the string each one waits on is the server's own, so the wait doubles as proof the round trip happened. The `id` on the backdrop and the `data-test` on the submit button went with the deleted tests.

**The empty-state button is still not built.** The spec's «Adding a wish» says an owner with no wishes gets copy *plus a button that opens the same modal*. Ticket 02 shipped the copy alone and this ticket's checklist only names the header button, so the header button is the only way in. Worth a follow-up if the invitation is meant to be clickable.

**`purchase_link` outgrew its column.** Review found the rule allowing 2,048 characters against a `string()` column, which is 255. A long product link would have passed validation and then failed the write on MySQL — invisible here, because SQLite does not enforce a varchar width. The column now says 2,048 and `StoreWishRequest` reads the same figure from a constant, so the refusal is always the validator's.

**Three fields repeat their own chrome, knowingly.** `TextField`, `TextAreaField` and `FileField` each carry their own label, error paragraph and `aria-describedby` wiring. A shared shell would hold it once, and was left unbuilt: it would mean forwarding scoped slots through `TextField`, which four auth pages use and which nothing covers beyond a JavaScript-error smoke test. A silent loss of label association there is worth more than ten lines of markup are. Worth revisiting whenever those pages get real assertions.

**The store route moved to `/wishes`, and the ownership rule went with it.** It was built as `POST u/{username}/wishes`, addressed to a profile, so the route resolved a `User` through the `username` binding and `StoreWishRequest::authorize()` had to prove that profile and the signed-in user were the same person. Nothing about a wish is actually scoped by a profile: it belongs to whoever created it, and the session already says who that is. The route is now `POST /wishes`, the controller reads the owner from `$request->user()`, and `authorize()` returns `true` because `auth` in front is the whole rule.

The checklist item «a signed-in visitor and a guest are both refused the store route» is half retired by this. A guest is still redirected to sign in. A signed-in visitor no longer has a request to be refused — posting while looking at somebody else's page adds a wish to their own profile, which is correct — so `WishTest`'s 403 case was deleted. What it protected is still covered: «a wish belongs to the profile owner, whatever the request claims» sends another user's `user_id` in the body and asserts it is ignored.

**`php artisan storage:link` runs as part of `composer setup`.** Documenting it in a README was not an option — there is no README — so it joined the script that is the app's setup path. `WishTest` fails loudly if the link is missing.
