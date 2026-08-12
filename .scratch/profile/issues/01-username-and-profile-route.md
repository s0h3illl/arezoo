# 01 — Username and the profile route

**What to build:** A name a user picks for themselves, and a URL built out of it. Today a user can be named in a URL only by their primary key, which is no way to build a link somebody is meant to send to their friends.

A user chooses a username while registering, as a fourth field beside name, email and password. It is unique, slug-safe, bounded in length, and refused for a small reserved list so that a username can never shadow a path the app routes. Uniqueness is matched without regard to case, so two people cannot hold names that differ only in capitalisation.

`/u/{username}` resolves through a route binding of its own, keyed on the username column. The panel's existing global `user` binding stays exactly as it is — keyed by primary key, excluding admins — so that adding a public URL shape does not quietly teach every admin route to accept a username too.

The new binding deliberately does **not** exclude admins. For reading and publishing wishes an admin is an ordinary user; ADR-0008 is a rule about the admin panel, not about the public site. It does exclude blocked users, so that a blocked user's profile is unreachable by anyone, enforced in one place rather than remembered in each controller.

`avatar` and `bio` land as nullable columns in the same pass. Nothing can set them yet — the settings screen is a later piece of work — but the profile's shape is settled now so that screen only has to add inputs.

All of these columns are edited **into the existing `create_users_table` migration**, not added as a new one. There is no production data, and `migrate:fresh --seed` is the reset path.

This ticket ends at a route that resolves and 404s correctly. The page it renders is 02.

**Blocked by:** nothing.

**Status:** ready-for-agent

- [x] `users` gains `username` (unique), `avatar` (nullable) and `bio` (nullable), edited into the existing users migration rather than a new one
- [x] Registration asks for a username alongside name, email and password, and creates the user with it
- [x] A username is required, slug-safe, length-bounded, and rejected when already taken — including when it differs only by case
- [x] A username matching a reserved path is rejected
- [x] The register page shows the field's validation errors in the same style as its existing fields, in Persian
- [x] `/u/{username}` resolves a user through a binding keyed on the username column
- [x] The panel's `user` binding is unchanged and still resolves by primary key, and no admin route accepts a username
- [x] An admin's own profile URL resolves like anyone else's
- [x] A blocked user's profile URL answers 404
- [x] An unknown username answers 404 through the app's existing Persian error page
- [x] A guest reaches the route without being redirected to login
- [x] `UserFactory` generates a unique username, and `DatabaseSeeder`'s admin account has one
- [x] `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean
