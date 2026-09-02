# 02 — How an email change is carried

Type: grilling
Status: resolved
Blocked by: none

## Question

[Charting](00-charting.md) settled the *behaviour*: the new address is mailed a link, **nothing is written until it is clicked**, and clicking sets the address and marks it verified in one step. It did not settle how that is carried.

Today `UpdateUserProfileInformation` does the opposite — writes `users.email` immediately, nulls `email_verified_at`, then mails the new address — so a typo costs the verified status *and* points the account at an address the user does not own.

Decide:

- **Where the pending address lives.** Two shapes, and they differ in what the UI can say:
  - **In a signed URL.** `URL::temporarySignedRoute(...)` carries the new address in the link itself; the signature makes it untamperable. No migration, no state, nothing to clean up — and it fits the house instinct that produced ADR-0005 (derive, don't store). But nothing can render "a change to `x@y.com` is pending", because nothing knows.
  - **In a `pending_email` column.** The screen can show what is outstanding and offer to cancel it, one change is in flight at a time, and the link is a plain token. Costs a migration and expiry semantics.
- **How long the link lives**, and what a user sees when they click an expired one. Fortify's own verification link uses `auth.verification.expire` (60 minutes by default); matching it is one fewer number to explain.
- **Uniqueness is checked twice or it is wrong.** The address is free when the link is sent and may be taken by the time it is clicked. Decide what the click does when it collides — and note this is exactly why the check cannot live only in the form request.
- **Whether the link requires an authenticated session.** Fortify's `verification.verify` sits behind `auth`, so a link opened on a phone where the user is signed out sends them to login first. Match it or diverge deliberately; a signed link that works while signed out is a link that works for whoever finds the email.
- **What happens to `email_verified_at`.** Charting says the new address lands verified. Confirm nothing in between can leave a user verified against an address they no longer hold.
- **What `UpdateUserProfileInformation` becomes.** It currently validates and writes both name and email. Email leaves it; decide whether it keeps the `updateProfileInformation` error bag and what the account screen's name form posts to.
- **The same endpoint serves `auth/VerifyEmail.vue`.** Charting put the form there too, so a registration typo is recoverable. Decide what that page posts, and where an unverified user lands after submitting — they cannot be sent to `/dashboard`, which is gated.
- **Where the lowercasing goes.** `config('fortify.username')` is `'email'` and `lowercase_usernames` is `true`, so `ProfileInformationController` lowercases the submitted email *before* the updater ever sees it. When email leaves that endpoint the lowercasing leaves with it — re-establish it in the new flow or drop it deliberately, but do not let it disappear by accident. Note the address is also lowercased at the point the link is *generated*, so a signed URL and the stored column cannot disagree about case.

- **Whether the old address is told.** A silent change means a hijacked session can move the account's email without the owner ever hearing about it. Notifying the old address is the usual defence; charting accepted no password confirmation anywhere, which makes this the only remaining guard.

## Answer

**Superseded and rewritten.** The answer below replaces an earlier one, and reverses the behaviour [charting](00-charting.md) assumed. Resolved during [What changing a username costs](03-what-changing-a-username-costs.md), after reading `UpdateUserProfileInformation` again: the stock Fortify action already does what the app needs, and the flow it was going to be replaced with bought one recoverable failure at the cost of a controller, two routes, a notification, a signed URL and a replay defence.

**Stock Fortify carries the email change.** `UpdateUserProfileInformation` keeps `email`, both its branches and `updateVerifiedUser()`: an address change writes `users.email` immediately, nulls `email_verified_at`, and sends the standard verification notification to the new address. The `$input['email'] !== $user->email` guard stays, so saving the form without touching the address mails nothing.

**Email posts to the profile form.** `PUT /user/profile-information` carries name, username, email, avatar and bio together — one form, one save button, one error bag (`updateProfileInformation`). Password keeps `PUT /user/password`. See [What changing a username costs](03-what-changing-a-username-costs.md) for the rest of that form.

**Nothing new is built.** No `EmailChangeController`, no `POST`/`GET /email/change`, no `URL::temporarySignedRoute`, no `sha1` replay fingerprint, no `VerifyNewEmailAddress` notification, no `status` sentinel table, no new throttle. The lowercasing stays where it already is: `config('fortify.username')` is `'email'` and `lowercase_usernames` is `true`, so `ProfileInformationController` lowercases the submitted address before the updater sees it — unchanged, because the endpoint does not change.

**The recovery path survives, and is the reason this is safe.** `user-profile-information.update` runs on `['web', 'auth']`, not `verified`. So `auth/VerifyEmail.vue` can still carry the form and an unverified user can still correct a registration typo — which is what lets the whole of `/dashboard/*` stay behind `auth` + `verified` without trapping anyone. That page keeps rendering `$request->user()->email`, which is now the *new*, unverified address rather than the old one. This amends `.scratch/auth-pages/`; only the mechanism changed.

### Accepted risks

**The address is written before it is proven.** A typo moves the account to an address the user does not own and costs them their verified status until they correct it from the verify notice. This is the failure the superseded design existed to prevent; it is accepted, and belongs in the spec's Further Notes as a decision.

**The old address is never notified.** Charting ruled out password confirmation anywhere, so nothing guards this: a hijacked session can move the account's address in silence, and after the change a password reset goes to the new address.

## Amendment

[How the profile form is tested](09-how-the-profile-form-is-tested.md) changed the route policy after this ticket closed: **`/dashboard` is the one exception to `verified`**, running on `auth` alone, so an unverified user reaches the account screen from the header and can correct a mistyped address there. `/dashboard/messages` and `/dashboard/withdrawals` keep `auth` + `verified`.

Consequences for this ticket: the account-menu row اطلاعات من is **enabled for every signed-in user** — only پیام‌ها and برداشت‌ها stay disabled behind the amber strip — and the email form on `auth/VerifyEmail.vue` is now a **kept convenience rather than the only door**. It was kept deliberately, with the duplication understood.
