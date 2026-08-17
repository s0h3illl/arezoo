# Spec: Editing a wish

Status: ready-for-agent

## Problem Statement

A wish can be created and deleted, and nothing in between. `.scratch/profile/spec.md` put editing out of scope deliberately — "Wishes can be created and deleted, not changed" — and the consequence has become the wrong one: a typo in a title, a price entered before the owner checked what the thing actually costs, or a cover photo picked in a hurry can only be fixed by deleting the wish and adding it again. Deleting is not a neutral undo. It soft-deletes, so the card leaves the grid for good, and the contributions to it stay attached to a wish nobody can see any more. Curating a profile currently means destroying the parts of it that are wrong.

Separately, and discovered while designing this: `HandleInertiaRequests::share()` hands the frontend `$request->user()` — the whole model. Every page in the app ships the signed-in user's `email`, `email_verified_at`, `is_admin`, `is_blocked`, `created_at` and `updated_at` inside the `data-page` attribute of the HTML document. It is the viewer's own record rather than a stranger's, so it is not a cross-user leak, but it is needless surface sitting in the page source and in anything that caches it, and `is_admin` in particular hands a staff-detector to anything that can read the DOM. The whole application reads exactly one field off that prop: `AppHeader.vue` uses `user.username`.

The two meet because the edit control needs to know whose wish it is looking at, and the honest way to answer that is `wish.user_id === auth.user.id` — which means the shared user has to be a shape we are willing to publish.

## Solution

An «ویرایش» control on each of the owner's own cards, opening a dialog with the wish's current values in it. Every field is editable, and the picture supports the three things an edit can mean: leave it, swap it, take it away.

Before that, the shared user prop is narrowed to a `UserResource` carrying only what the frontend is entitled to see, and the card learns to answer the ownership question itself from data it already has.

## User Stories

1. As an owner, I want to fix a wish's title, so that a typo does not cost me the wish and its history.
2. As an owner, I want to correct a price I guessed at, so that the progress bar measures against the real cost.
3. As an owner, I want to add a description or a product link I did not have when I published, so that a wish can become more complete over time.
4. As an owner, I want to replace a cover photo, so that a picture chosen in a hurry is not permanent.
5. As an owner, I want to remove a cover photo entirely, so that a bad picture can be no picture rather than a different bad picture.
6. As an owner, I want to edit a wish people have already paid towards, so that money arriving does not freeze my page.
7. As an owner, I want editing to leave every contribution alone, so that fixing a title cannot cost me money I have earned.
8. As an owner, I want the dialog to open already filled in, so that changing one field does not mean retyping the other four.
9. As an owner, I want the modal to stay open and keep what I typed when something is rejected, so that a mistake in one field does not cost me the rest.
10. As an owner, I want the card to show the change when the dialog closes, so that I can see it worked.
11. As a visitor, I want no edit control on someone else's profile, so that I cannot act on wishes that are not mine.
12. As a visitor, I want the edit route to refuse me even if I call it directly, so that hiding the control is not the only thing protecting it.
13. As a user, I want my own account details to stay out of the page source, so that reading the HTML does not reveal my email or whether I am staff.
14. As an owner of the system, I want swapped-out cover files removed from disk, so that editing does not silently grow storage forever.

## Implementation Decisions

### The shared user

- **`App\Http\Resources\UserResource`** — a user as the public application reads them: `id`, `name`, `username`, `avatar`, `bio`. No email, no `is_admin`, no `is_blocked`, no timestamps. `$wrap = null`, following `WishResource` and `Admin\UserResource`.
- The name is deliberately the unqualified one. The admin panel's narrower, privileged shape stays `App\Http\Resources\Admin\UserResource` — **admin is the exemption by namespace, not by name**, which is the convention the resource folder already follows.
- `HandleInertiaRequests::share()` publishes that resource instead of the model. `ProfileController` uses it too, replacing its hand-rolled `$user->only(['name', 'username', 'avatar', 'bio'])`.
- `resources/js/types/user.ts` narrows to match. **This breaks the admin pages**, which currently derive their types from it — `admin/users/Index.vue` and `admin/users/Show.vue` both do `Omit<User, 'is_admin' | 'updated_at'>` and then read `email`, `email_verified_at` and `is_blocked`. They get a new `resources/js/types/admin/user.ts` mirroring `Admin\UserResource`, which is where they should have been reading from all along — `types/admin/wish.ts` already exists and `user.ts` never got written.
- **`ProfileController` stops sending `is_owner`.** The page derives it from the profile user's `id` and `auth.user`, which is the same question the card now answers for itself, asked once per page instead of twice.

### Editing

- **`PUT wishes/{wish}`**, named `wishes.update`, `auth` middleware then `->can('update', 'wish')` — the same shape as the delete route beside it. `WishPolicy::update()` is identical to `delete()`.
- **`UpdateWishRequest extends StoreWishRequest`**, spreading `parent::rules()` and adding `remove_thumbnail`. `MAX_PRICE` is the `unsignedInteger` column's ceiling rather than a preference, and a database fact should be written once.
- **Every field is editable, always** — including on a wish that has received money, and including a price set below what has already landed. This is ADR-0009, and it is the same stance ADR-0003's delete already takes. `CONTEXT.md`'s **Wish** entry is amended to say so.
- **Editing touches no contribution.** Nothing about a contribution is derived from a wish's fields, so this is a property of the schema rather than something the controller has to be careful about — but it is asserted in a test, because it is the reason the decision is safe.

### The picture

- The dialog shows the current cover with a **«حذف تصویر»** control beside it, and a file input that replaces it. On the wire: `thumbnail: File | null` and `remove_thumbnail: boolean`.
  - no file, not removed → the column is untouched
  - a file → stored, and the old file is deleted from disk
  - removed → the column is nulled, and the old file is deleted from disk
  - both → **the file wins.** The dialog cannot produce this (choosing a file clears the flag); the server defines the precedence anyway rather than leaving it to argument order.
- **The old file is deleted on swap and on removal.** Nothing in the app has ever deleted a file before — deleting a wish deliberately leaves its cover, because the wish is only soft-deleted — so this is the first place storage is reclaimed, and it is reclaimed only where the reference is being overwritten.
- **`WishFactory` stops sharing one file across every wish.** Its `static::$thumbnail` cache exists so that seeding does not write the 830 KB fixture ~90 times, but it means every seeded wish points at one path, and delete-on-swap would blank ~40–90 covers at once. The cache goes, and the factory points at a **new few-KB fixture** so that per-wish copies stay cheap. `tests/fixtures/thumbnail.jpg` stays as it is for the upload tests, which need a real-sized file.

### Reaching the server

- Submitted with **`form.put()`** — except when a new picture is attached, where it falls back to `form.post()` with `_method: 'put'`.
- This is not a style choice. Inertia passes the verb through verbatim: `@inertiajs/core@3.6.1` contains no `_method` handling anywhere (`transformUrlAndData` converts data to `FormData` when files are present and does nothing to the method), and the `<Form>` component renders `<form method="put">` only as an inert DOM attribute before calling `router.submit('put', …)`. PHP does not parse a multipart body for anything but POST — probed directly: a PUT with a file arrives with 849,211 bytes of raw body and both `$_POST` and `$_FILES` empty.
- **A feature test cannot catch this.** Laravel's test client builds the `Request` object directly and bypasses PHP's global parsing, so `$this->put(…, ['thumbnail' => $file])` passes happily against code that fails in a real browser. The branch carries a comment saying so, because the failure mode is a green test suite.
- `forceFormData` is not used, for the reason `AddWishDialog` already documents: Pest's browser plugin cannot parse a multipart body, so every browser-tested submission would arrive empty.

### The card and the dialog

- **`WishCard` answers the ownership question itself**, from `wish.user_id === auth.user?.id`. The `deletable` prop goes. Its `Pick` gains `user_id` and `purchase_link` — the latter is not rendered, and the contract comment is amended to say the narrowing now covers what the card draws *and* what its dialog edits.
- **`EditWishDialog.vue` stands alone beside `AddWishDialog.vue`**, with its own copy of the five fields. The folder is one component per action and each reads top to bottom; the cost is that a sixth field would have to be added in two places.
- **Field ids are scoped per wish** (`wish-{id}-title`). reka-ui only mounts dialog content while it is open, so a grid of cards does not actually collide, but scoped ids are free and let a browser test address one specific card's dialog.
- **Footer:** «ویرایش» and «اشتراک» group together, «حذف» is pushed to the far end by an auto margin — three controls, one destructive, and it should not sit between the two that get pressed casually. A visitor still sees «اشتراک» alone. A new `PencilIcon.vue`.
- **No toast on success.** Adding a wish does not toast either; the changed card is its own confirmation.

## Testing Decisions

- `UserResourceTest` or an addition to the existing coverage: the shared prop carries `id`, `name`, `username`, `avatar`, `bio` and **does not carry** `email`, `is_admin`, `is_blocked` or the timestamps. This is the assertion that stops the leak coming back.
- `EditWishTest` (Feature): an owner edits title and price; optional fields are set and cleared; a missing title or price is refused; a signed-in visitor and a guest are both refused; a swap stores the new file and deletes the old; a removal nulls the column and deletes the file; a file plus the remove flag keeps the file. **An edit that drops the price below what has been received leaves every contribution and the owner's derived total untouched.**
- `EditWishTest` (Browser): open the dialog from a card, change the title, submit, and read the database. No upload — the Pest browser cannot post multipart.
- Existing tests that change: anything asserting `is_owner` in `ProfileTest`, the admin user tests if they assert an exact prop shape, and any test relying on two factory wishes sharing a thumbnail path.
- `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean, `bun run types:check` clean.

## Out of Scope

- **Restoring a soft-deleted wish**, and any UI that reveals one exists.
- **A history of edits**, and showing a contributor what a wish looked like when they gave. ADR-0009 names this as the thing to build first if the unrestricted stance is ever revisited.
- **Cleaning up cover files already orphaned**, including the ones a delete has left behind. Only files this feature overwrites are reclaimed.
- **The account-settings screen.** `UserResource` publishes `avatar` and `bio`; nothing can still set them.
- **The wish detail page**, and `purchase_link` appearing anywhere it renders. The card now carries the field for the dialog's sake and still does not draw it.
- Editing anything from the admin panel.

## Further Notes

- `.scratch/profile/spec.md` lists editing under Out of Scope. That line is amended to point here rather than being left to contradict the app.
- ADR-0009 records the unrestricted stance. No ADR is proposed for the PUT/multipart branch — it is three lines and trivially reversible — but it must carry a comment, because the reason for it is invisible and the test suite will not defend it.
