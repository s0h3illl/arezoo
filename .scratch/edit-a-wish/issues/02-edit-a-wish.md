# 02 — Editing a wish

**What to build:** An «ویرایش» control on each of the owner's own cards, the dialog behind it, and the route that serves it.

Every field is editable, on any wish, at any time — including one people have already paid towards, and including a price set below what has already landed. That is ADR-0009, and it is the same stance deleting already takes: a profile is the owner's to curate. Nothing about a contribution is derived from a wish's fields, so an edit cannot move money; a test asserts it anyway, because that is the reason the decision is safe.

The picture is the interesting part, because a file input cannot be prefilled and an edit can mean three different things about it. The dialog shows the current cover with a «حذف تصویر» control beside it and a file input that replaces it, and the submission carries `thumbnail` and `remove_thumbnail`: neither means untouched, a file means swapped, the flag alone means nulled. If both arrive the file wins — the dialog cannot produce that, but the server settles it rather than leaving it to argument order.

**The old file is deleted on swap and on removal.** Nothing in the app has ever deleted a file before; deleting a wish deliberately leaves its cover, since the wish is only soft-deleted. This is the one place a reference is being overwritten, so it is the one place storage is reclaimed. It forces a factory change: `WishFactory` caches one stored fixture in `static::$thumbnail` and points every wish at it, so delete-on-swap would blank ~40–90 seeded covers at once. The cache goes and the factory copies a **new few-KB fixture** instead, keeping seeds cheap; `tests/fixtures/thumbnail.jpg` stays at full size for the upload tests that need it.

Submission is `form.put()`, falling back to `form.post()` with `_method: 'put'` **only when a new picture is attached**. Inertia passes the verb through verbatim — `@inertiajs/core@3.6.1` has no `_method` handling at all, and `<Form method="put">` is an inert DOM attribute before `router.submit('put', …)` — and PHP parses a multipart body for POST only. Probed directly: a multipart PUT arrives with 849,211 bytes of raw body and both `$_POST` and `$_FILES` empty. **A feature test cannot catch this**, because Laravel's test client builds the `Request` directly and skips PHP's parsing, so the branch carries a comment saying so. `forceFormData` is not an escape: Pest's browser plugin cannot parse multipart, which is why `AddWishDialog` avoids it.

`WishCard` stops taking `deletable` and judges ownership itself from `wish.user_id === auth.user?.id`. Its `Pick` gains `user_id` and `purchase_link`; the link is still not drawn, and the contract comment is amended to say the narrowing now covers what the card renders *and* what its dialog edits.

**Blocked by:** 01 — Narrowing the shared user.

**Status:** done

- [x] `PUT wishes/{wish}` named `wishes.update`, behind `auth` and `->can('update', 'wish')`
- [x] `WishPolicy::update()` matching `delete()`
- [x] `UpdateWishRequest extends StoreWishRequest`, adding `remove_thumbnail`
- [x] An owner sees «ویرایش» on their own cards; a signed-in visitor and a guest see none
- [x] The update route refuses a signed-in visitor and a guest, not merely hiding the control
- [x] The dialog opens with the wish's current values already in it
- [x] All five fields are editable, and optional fields can be cleared as well as set
- [x] A rejected submission keeps the dialog open with its errors inline and everything typed still there
- [x] A wish that has received money edits as freely as one that has not, including to a price below what it holds
- [x] **After an edit, every contribution to the wish still exists and the owner's derived total is unchanged**
- [x] Swapping the cover stores the new file and deletes the old one from disk
- [x] Removing the cover nulls the column, deletes the file, and the card falls back to its striped placeholder
- [x] A submission carrying both a file and the remove flag keeps the file
- [x] `WishFactory` gives each wish its own cover file, copied from a small fixture
- [x] `WishCard` takes no `deletable` prop and derives ownership from `wish.user_id`
- [x] Footer reads «ویرایش» «اشتراک» … «حذف», with the destructive control at the far end
- [x] Field ids are scoped per wish
- [x] A browser test opens the dialog, changes the title, submits, and reads the database
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean, `bun run types:check` clean
