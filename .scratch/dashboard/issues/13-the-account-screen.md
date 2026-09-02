# 13 — The account screen at `/dashboard`

**What to build:** A signed-in user reaches `/dashboard` and edits their own details: name, username, email and bio on one form with one save button, and a password change beside it behind their current password. Saving shows Fortify's `status` on the page, the way the auth pages already show it; a validation error appears against the field that caused it.

**An unverified user can reach this page.** It is the screen that corrects a mistyped address, so it must not sit behind the verification that a wrong address is blocking.

**Blocked by:** Nothing. 12 shipped in `d72d30f`; 11 is closed.

**Status:** ready-for-agent

Spec: [The routes](../spec.md), [The account screen](../spec.md). Decisions: [charting](00-charting.md), [How an email change is carried](02-how-an-email-change-is-carried.md), [How the profile form is tested](09-how-the-profile-form-is-tested.md).

- [ ] `/dashboard` runs on `auth` **only** — deliberately the one exception. The other two dashboard routes, when they land, carry `auth` + `verified`.
- [ ] A root dashboard controller, in the root namespace beside the admin one. Nothing in this section is called a profile — that word belongs to the public page.
- [ ] Name, username, email and bio post to the existing profile-information endpoint under one save button. No new controller and no new route.
- [ ] The password form keeps Fortify's own endpoint, which already demands the current password and so gates itself.
- [ ] The email change is **stock Fortify**: the new address is written immediately, the verified timestamp is nulled, and the standard verification mail goes out. No magic link, no signed URL, no new notification.
- [ ] Nothing is shown on submit — no dialog, no confirmation step, and **no warning copy** about renaming breaking shared profile links. Wish links are id-based and survive a rename regardless.
- [ ] Success is reported through the shared `status` prop, not a toast: both forms post to Fortify endpoints, so Fortify dictates the mechanism. `HandleInertiaRequests::share()`, the Fortify response classes and `AppLayout` are all left alone.
- [ ] The two sentinels `profile-information-updated` and `password-updated` are mapped to Persian **in this page**, as `auth/VerifyEmail.vue:34` already maps its own. Printing either raw shows English to a Persian user. The sentinel itself is used exactly as flashed.
- [ ] A failed save shows its inline field error and nothing else. No error toast.
- [ ] Browser test: name, username and bio are filled, saved, and all three are read back from the database.
- [ ] Browser test: the email is changed, and the row holds the new address with the verified timestamp nulled. The mail is never opened — this stops at database truth.
- [ ] Browser test: a username someone else holds is submitted and the error renders on the page. This is the only proof the form displays errors at all.
- [ ] Browser test: a smoke pass on the page with no JavaScript errors.
- [ ] Browser test: after a successful save the Persian confirmation renders on the page. Asserted here, as a rendered element.
- [ ] The password form is deliberately untested: stock Fortify on its own endpoint.
