# 01 — How an avatar is carried

Type: grilling
Status: resolved
Blocked by: none

## Question

[Charting](00-charting.md) put avatar and bio on the account screen, closing a real gap: both are rendered on the public profile (`Profile.vue:65,85`) and nothing in the app can set either. Bio is a textarea and needs no ticket. The avatar is a file, and files in this repo have opinions attached.

**There is precedent, and it should be followed unless a reason not to is recorded.** A wish thumbnail already does the whole journey:

- `StoreWishRequest`: `['nullable', 'image', 'max:2048']`, with the ceiling a named `MAX_THUMBNAIL_KILOBYTES` constant.
- `WishController::store()`: `$request->file('thumbnail')?->store('wishes', 'public')` — the column holds a **path**.
- `WishController::thumbnailChange()`: a `remove_thumbnail` boolean for deletion, the old file `Storage::disk('public')->delete()`d on replace, and a `RuntimeException` when `store()` returns `false`.
- `WishResource`: `Storage::disk('public')->url($this->thumbnail)` — the path becomes a URL at the boundary, never in the column.
- `public/storage` is linked; `tests/fixtures/thumbnail.jpg` and `wish-cover.jpg` exist for tests.

Decide:

- **Does the avatar simply follow the thumbnail pattern?** Same disk, an `avatars/` directory, `image` rule, a `remove_avatar` boolean, delete-on-replace. If it diverges anywhere, record why.
- **What is the size ceiling?** The thumbnail's 2MB is sized for a wish cover displayed large. An avatar renders at roughly 40–96px. A smaller ceiling is a kindness to the person uploading; an identical one is a kindness to whoever reads the code.
- **`UserResource` publishes `avatar` raw** — `'avatar' => $this->avatar` — unlike `WishResource`, which converts. Nothing has caught this because `users.avatar` is null everywhere: no factory or seeder sets it, so the app has never rendered one. `Profile.vue` binds it straight to `:src`, so the current contract can only mean "a full URL". The moment an upload writes a path, `UserResource` has to convert and `types/user.ts`'s `avatar: string | null` changes meaning. Decide the shape and note every reader that moves with it.
- **Is there any image processing at all** — square-cropping, resizing, stripping EXIF? **There is no `gd` extension in this environment.** Say no, or say what the dependency is and that it needs approval per `CLAUDE.md`.
- **What replaces a browser test.** The Pest browser cannot POST multipart — its server parses urlencoded only, so a forced `FormData` body arrives empty and reads as a broken `v-model`. `UploadedFile::fake()->image()` also throws without `gd`, which is why `tests/fixtures/` holds real files. Decide what actually covers the upload: a feature test posting a fixture, plus a browser smoke test that never touches the file input.
- **What an avatar-less user sees on the account screen.** `Profile.vue` already has the answer for the public side — `reka-ui`'s `AvatarRoot`/`AvatarImage`/`AvatarFallback` with an emerald first-letter fallback. Confirm the editor reuses it rather than inventing a second empty state.

## Answer

**The avatar follows the wish-thumbnail pattern on the server and diverges from it in the browser.** The column holds a path on the `public` disk under `avatars/`; the file that reaches the server has already been resized by the client.

### The browser downscales before posting

`gd` is confirmed absent — `function_exists('imagecreatetruecolor')` is `false` — so nothing server-side can resize, and the thumbnail's approach of storing whatever arrives does not transfer. It was never tested against an avatar's two aggravating properties:

- **Avatars render tiny and in bulk.** The largest is 96px (`size-24` on `Profile.vue`); `ContributorRow.vue` is 40px and a wish page loads seven at a time. Storing a 2MB phone photo means 14MB of transfer for seven forty-pixel circles.
- **A phone photo carries EXIF, including GPS**, and the file is served publicly from a page that also carries the person's real name and username. Nothing in the pipeline strips it.

So the file is resized **before** it is posted: a canvas pass takes the largest centred square, scales it to **512×512**, and re-encodes to **WebP** at quality 0.85 — roughly 45KB for a photograph. Re-encoding strips EXIF as a side effect, which is the whole of the fix for the location leak. No dependency is added and no `gd` is needed; the browser does the work.

Three consequences, each deliberate:

- **The centre crop is not a loss of control**, because the app already centre-crops every avatar: both render sites use `object-cover` inside `overflow-hidden rounded-full`. Making the stored file match what is displayed changes nothing the user sees. A drag-and-zoom cropper was considered and cut — new component, drag maths, and untestable in the Pest browser.
- **WebP needs no fallback branch.** A browser that cannot encode it has `toBlob` hand back a PNG instead — larger, still valid, still accepted by the `image` rule.
- **The file posted is not the file chosen.** Accepted, and worth a comment at the call site so it does not read as a bug.

### The server validates bytes and nothing else

```php
private const int MAX_AVATAR_KILOBYTES = 512;

'avatar' => ['nullable', 'image', 'max:'.self::MAX_AVATAR_KILOBYTES],
```

The constant mirrors `StoreWishRequest::MAX_THUMBNAIL_KILOBYTES` in style. 512KB is a roomy backstop over the canvas's ~45KB, and it is the *only* gate for anything posted around the browser.

**No `dimensions` rule.** `getimagesize()` is available without `gd` — verified — so the rule *could* be used, and it is still the wrong call: nothing server-side ever decodes the image. It stores bytes and serves bytes, so pixel dimensions cost the server nothing. Matching the canvas exactly (`max_width=512`) was rejected for coupling the server rule to a frontend constant, so that raising the canvas to 1024 later would silently start rejecting valid uploads.

### The path becomes a URL once, on the model

`users.avatar` has always been published **raw** — `UserResource` does `'avatar' => $this->avatar` and so does `ContributionResource`, both bound straight to `:src` in Vue. Nothing has caught it because the column is null everywhere: no factory or seeder sets it, so the app has never rendered an avatar. The current contract can only mean "a full URL", and storing a path breaks it.

Fixed once, on the model:

```php
protected function avatarUrl(): Attribute
{
    return Attribute::get(fn (): ?string => $this->avatar === null
        ? null
        : Storage::disk('public')->url($this->avatar));
}
```

Both resources publish `'avatar' => $this->avatarUrl`, so **the wire format and every frontend type are unchanged** — `user.ts`, `contribution.ts`, `Profile.vue`, `ContributorRow.vue` and the `hasAll([... 'avatar' ...])` assertions in `ProfileTest` all stand. The inbox row ([What the inbox shows](04-what-the-inbox-shows.md)) gets it free.

`WishResource`'s inline `Storage::disk('public')->url($this->thumbnail)` is **left alone**. It is the same idea in one place, and `thumbnail` has exactly one resource; `avatar` already had two before this map added a third, which is the whole reason the rule moved to the model. Overriding the `avatar` attribute itself was rejected — a model that is written a path and reads back a URL stops meaning one thing.

### It posts with the rest of the profile form

Avatar and bio join name on `PUT /user/profile-information` under one save button — not a dedicated upload endpoint saving on pick. One commit point for the screen, matching how a wish form already works.

The mechanics that follow:

- The request is `POST` with `_method: 'put'` and `forceFormData`, since multipart cannot be sent as a real `PUT`.
- Removal is a **`remove_avatar` boolean**, exactly as `remove_thumbnail` is today.
- Fortify's `ProfileInformationController` calls `$updater->update($request->user(), $request->all())`, and `Request::all()` merges uploaded files — so the `UploadedFile` reaches `UpdateUserProfileInformation` untouched. Its `@param array<string, string> $input` docblock stops being true and must widen.
- `WishController::thumbnailChange()` is the precedent for the write itself and transfers whole: no file and not removing returns `[]` and changes nothing; a file is `->store('avatars', 'public')`d, the old one `Storage::disk('public')->delete()`d, and the new path returned; removing without a file returns `null` and deletes; a `store()` returning `false` throws `RuntimeException`. Lift the shape, not the copy.

Deleting the old file is safe without a guard: both render sites already comment that the `AvatarFallback` "renders whenever the picture has not loaded, which covers both a user who has none and one whose file has gone missing."

### The editor reuses the existing empty state

`AvatarRoot`/`AvatarImage`/`AvatarFallback` with the emerald first-letter fallback, as `Profile.vue` and `ContributorRow.vue` both do. No second empty state is invented, and the preview beside the file input is the same component the public page renders.

### Testing

The pattern already exists in `tests/Feature/WishTest.php` and transfers directly:

- `Storage::fake('public')`, then `Storage::disk('public')->assertExists()` on the stored path.
- **Happy path posts a real fixture** — `new UploadedFile(base_path('tests/fixtures/thumbnail.jpg'), 'a.jpg', 'image/jpeg', test: true)` — because `UploadedFile::fake()->image()` throws without `gd`. `tests/fixtures/thumbnail.jpg` is reused; no new fixture is needed, since the server accepts any `image` and never sees the canvas's WebP in a feature test.
- **Refusals use `UploadedFile::fake()->create()`**, which needs no `gd` because it writes bytes rather than pixels: a PDF for the wrong type, and a `create('huge.jpg', 3_000, 'image/jpeg')` for the size ceiling.
- Removal and replace each get a test, including that replacing deletes the previous file.

**The upload has no browser test, and this is the one place the answer is a limitation rather than a choice.** The Pest browser parses urlencoded bodies only, so a forced `FormData` arrives empty and reads as a broken `v-model`; the browser tests for wishes work around this by asserting `thumbnail->toBeNull()` and never touching a file input. The same applies here, and it means **the canvas downscale — the load-bearing part of this decision — is not covered by any automated test.** Record it in the spec as a known gap rather than letting a reviewer discover it.
