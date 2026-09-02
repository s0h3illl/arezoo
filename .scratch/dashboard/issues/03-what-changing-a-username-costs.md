# 03 — What changing a username costs

Type: grilling
Status: resolved
Blocked by: none

## Question

`CONTEXT.md` already commits to this: a username is "theirs to change — which frees the one they gave up, so a link shared under an old username may later lead somewhere else." The commitment exists; no screen has ever honoured it, and the consequences have never been priced.

`UsernameValidationRules` is already extracted as a trait, used by `CreateNewUser`. The `User` model lowercases on write via an accessor, and its own comment anticipates this screen: "every way in — registration, a factory, a seeder, **the settings screen when it lands** — leaves the unique index enforcing the rule."

Decide:

- **What the user is told before they confirm.** `/u/{username}` is the whole of a profile's address and the app's only share target — `shareLink(show(wish.id).url)` aside, every profile link a user has ever sent breaks. Silent breakage is the wrong default; decide the copy and whether it is inline text, a confirmation step, or a dialog.
- **Whether the freed username is really free immediately.** `CONTEXT.md` says yes, and says the consequence out loud: a link shared under an old username "may later lead somewhere else" — someone else's profile, wearing a name a stranger's audience still trusts. Confirm that is still wanted now there is a screen that makes it easy, or record a hold period and amend the glossary.
- **Whether changes are rate-limited.** Nothing stops a user cycling usernames to squat names as they free up. Decide a limit, or decide the app does not care and say why.
- **Whether `UsernameValidationRules` covers an update at all.** It was written for creation. A uniqueness rule that does not ignore the current user refuses a no-op save; check what it actually does before assuming it transfers, the way `UpdateUserProfileInformation` had to use `Rule::unique('users')->ignore($user->id)` for email.
- **Whether reserved names exist.** `/u/{username}` sits under its own prefix so it cannot collide with `admin`, `terms` or `dashboard` — but `support`, `help` and `arezoo` are names a platform usually keeps. Decide, or record that it does not.
- **What the screen shows.** The current address is a fact worth rendering (`arezoo.app/u/soheil`) so the user sees what they are editing rather than a bare field.

- **Which form the field posts to.** Graduated here from the map's fog once [How an avatar is carried](01-how-an-avatar-is-carried.md) settled the screen's shape: name, bio and avatar are **one** form on `PUT /user/profile-information`, password keeps its own, and email leaves for its own flow. Username either joins the profile form or gets its own — and the answer is not obvious, because changing a username breaks every link the user has shared, which is a heavier act than editing a bio and may not want to share a save button with one.

Two facts about Fortify's config that look relevant and are not, so nobody wires them up wrong: `fortify.username` is `'email'` — it names the *login identifier*, not this column — and `fortify.lowercase_usernames` therefore lowercases the email field in `ProfileInformationController`, not this one. Our `username` is already lowercased by the model's own `Attribute` accessor, which the model comments on directly.
## Answer

**One form.** Name, username, email, avatar and bio all post to `PUT /user/profile-information`. Password keeps Fortify's `PUT /user/password`. No new controller, no new route, no second save button — the username field is an ordinary field on the profile form, and `UpdateUserProfileInformation` is where the work lands.

**Nothing is shown when the form is submitted.** No confirmation step, no dialog, no interstitial. The form saves; if validation fails, the error messages are the only thing rendered back.

**No warning copy anywhere.** The `arezoo.app/u/` prefix on the input and the helper line about link breakage were both proposed and both dropped. The screen shows the fields and nothing else. Arezoo does not tell a user that renaming breaks the links they have shared — a decision to record in the spec's Further Notes, not a gap to close later. Note that only *profile* links break: wish links are `show(wish.id)`, id-based, and survive a rename untouched.

**The freed username is free immediately.** `CONTEXT.md:29` stands unamended — "theirs to change — which frees the one they gave up, so a link shared under an old username may later lead somewhere else." No hold period, no reservation column, no expiry, and therefore no undo for a rename the user regrets. A hold would cost a column, a rule, an expiry and a question about whether the original owner can take the name back, and would still only delay the same outcome by its duration.

**No rate limit.** Nothing throttles renames beyond the route's existing middleware. With no hold period there is nothing to squat *for*: a name becomes free only when the person holding it renames, which no attacker can trigger, so cycling usernames wins nothing a single change would not.

**`UsernameValidationRules` does not transfer as written.** The one real defect this ticket found, confirmed in the code rather than assumed:

`validateUsernameIsFree()` asks `User::query()->withUsername($username)->exists()` — which counts the signed-in user's own row. Saving the profile form without touching the username field therefore fails with «قبلاً استفاده شده», exactly the no-op-save trap that made `UpdateUserProfileInformation` reach for `Rule::unique('users')->ignore($user->id)` on email.

The fix keeps the trait as the single home of the rules: `usernameRules()` takes an optional user to ignore and passes it to the closure, which excludes that key from the existence check. `CreateNewUser` keeps calling `usernameRules()` with no argument and its behaviour does not move; `UpdateUserProfileInformation` calls it with the user being edited. The reserved-name half of the closure applies on update exactly as it does on creation — a user may not rename themselves onto a path the app routes.

**Reserved names already exist** — `RESERVED_USERNAMES` holds 16 entries (`admin`, `api`, `u`, `terms`, `settings`, `storage`, and the auth paths). `support`, `help` and `arezoo` are added: names a platform keeps for itself, and a one-line change with no mechanism behind it. This is the only call in this ticket taken without being put to the dev.

**Email reverted to stock Fortify.** Decided in the course of this ticket and applied to [How an email change is carried](02-how-an-email-change-is-carried.md), which it supersedes: `UpdateUserProfileInformation` keeps `email`, writing the new address immediately, nulling `email_verified_at` and sending the standard verification notification. There is no `EmailChangeController`, no signed URL and no `VerifyNewEmailAddress` mail.

Two facts about Fortify's config remain true and still trap nobody: `fortify.username` is `'email'`, so `lowercase_usernames` lowercases the *email* input in `ProfileInformationController` — this column is lowercased by the model's own `Attribute` accessor, on every path in.
