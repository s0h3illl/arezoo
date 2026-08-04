# 05 — Admin sets a user's password

**What to build:** A way back in for a user who has lost access to their email and therefore cannot use the password-reset flow. An admin types a new password for them on their detail view; the user signs in with it and is told what it is out of band.

This was chosen over triggering a reset link, knowingly: an admin who can set any password can sign in as any user. The spec records that as an accepted risk. Do not add an audit log or a notification here — both are explicitly out of scope.

**Blocked by:** 04 — User detail view

**Status:** ready-for-agent

- [ ] An admin can set a new password for a user from their detail view, after a confirmation step
- [ ] The new password is held to exactly the same rules the app already applies at registration and password reset — no weaker path
- [ ] A password failing those rules is rejected with the same Persian validation messages used elsewhere
- [ ] The user can sign in with the new password immediately
- [ ] The user's existing sessions stop working, so the change takes effect everywhere at once
- [ ] The user's old password no longer works
- [ ] Setting a password on a blocked user does not unblock them — they still cannot sign in
- [ ] Only an admin can do this; a non-admin attempting it gets a 404
- [ ] Pint clean, larastan clean
