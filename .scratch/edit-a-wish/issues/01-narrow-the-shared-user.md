# 01 — Narrowing the shared user

**What to build:** A `UserResource` for the shared `auth.user` prop, so that the frontend stops receiving the whole user model.

`HandleInertiaRequests::share()` publishes `$request->user()`, which serialises every visible column. Each page therefore carries the signed-in user's `email`, `email_verified_at`, `is_admin`, `is_blocked`, `created_at` and `updated_at` in the `data-page` attribute of the HTML document, where it sits in the page source and in anything that caches it. It is the viewer's own record rather than a stranger's, so this is not a cross-user leak — but none of it is needed, and `is_admin` in particular tells anything reading the DOM who is staff.

The whole application reads one field off that prop: `AppHeader.vue:61` uses `user.username`.

`App\Http\Resources\UserResource` becomes the public shape — `id`, `name`, `username`, `avatar`, `bio` — with `$wrap = null` like its siblings. The unqualified name is deliberate: the panel's privileged shape stays `Admin\UserResource`, because admin is the exemption by namespace, not by name. `ProfileController` uses the same resource in place of its hand-rolled `$user->only([...])`.

Narrowing `resources/js/types/user.ts` breaks the two admin pages, which derive from it today — `admin/users/Index.vue:20` and `admin/users/Show.vue:20` both do `Omit<User, 'is_admin' | 'updated_at'>` and then read `email`, `email_verified_at` and `is_blocked`. They get `resources/js/types/admin/user.ts` mirroring `Admin\UserResource`, which is where they should have been reading from already; `types/admin/wish.ts` exists and `user.ts` was simply never written.

`id` is in the shape because the next issue needs it: a card judges whether a wish is the viewer's own by comparing `wish.user_id` against it. With that available, `ProfileController` stops sending `is_owner` and the page derives it.

**Blocked by:** nothing.

**Status:** done

- [x] `App\Http\Resources\UserResource` carries `id`, `name`, `username`, `avatar`, `bio` and nothing else
- [x] `HandleInertiaRequests` shares it in place of the model
- [x] `ProfileController` uses it in place of `$user->only([...])`
- [x] **A test asserts the shared prop does not carry `email`, `is_admin`, `is_blocked`, `email_verified_at` or the timestamps**
- [x] `resources/js/types/user.ts` narrows to the public shape
- [x] `resources/js/types/admin/user.ts` mirrors `Admin\UserResource`; both admin user pages read from it and stop deriving from `User`
- [x] `ProfileController` stops sending `is_owner`; `Profile.vue` derives it from the profile user's `id` and `auth.user`
- [x] `AppHeader` still links to the signed-in user's own profile
- [x] Pint clean, larastan clean, `bun run types:check` clean

## Comments

Verified in isolation before commit: `WishCard.vue`'s ownership derivation and `CONTEXT.md`'s ADR-0009 note belong to issue 02 and were kept out of this slice, along with the rest of issue 02's backend and frontend work, which was in progress alongside this and is not yet done.
