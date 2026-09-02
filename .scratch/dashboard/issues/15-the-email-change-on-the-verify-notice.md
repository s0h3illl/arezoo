# 15 — The email change on the verify notice

**What to build:** A user who mistyped their email at registration corrects it from the verify-email notice itself, without having to navigate anywhere. This closes a trap where such a user could neither fix the address nor start over — usernames are unique, so their abandoned account still holds the name they wanted, and both the account and the name were unrecoverable.

The account screen is now the primary door for this; the notice page's form is a kept convenience, and the duplication is deliberate.

**Blocked by:** 13.

**Status:** ready-for-agent

Spec: [The email change is stock Fortify](../spec.md). Decisions: [How an email change is carried](02-how-an-email-change-is-carried.md), [How the profile form is tested](09-how-the-profile-form-is-tested.md).

- [ ] The verify-email notice carries an email-change form beside its existing resend and sign-out.
- [ ] It posts to the same profile-information endpoint as the account screen. One endpoint serves both entry points; there is no second route and no second updater.
- [ ] This works because that endpoint runs on `auth` without `verified`. The middleware fact is load-bearing and is already pinned by a test from ticket 12.
- [ ] The page keeps its own sign-out link even once the account menu also offers one — it is the recovery path the copy points at.
- [ ] Browser test: an unverified user fills the form on the notice page, saves, and the row changes. This test exists **only** because the duplicate form was kept — the account screen's own email test says nothing about whether this second form is wired.
- [ ] Two accepted risks are carried into the spec, not solved here: an unproven address is written, so a typo costs the verified status until corrected; and the old address is never notified, so with no password confirmation anywhere a hijacked session can move it in silence.
