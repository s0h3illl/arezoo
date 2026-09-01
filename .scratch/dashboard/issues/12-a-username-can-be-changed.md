# 12 — A username can be changed

**What to build:** A signed-in user can save their account details without the form rejecting the username they already hold. Today the uniqueness check counts the user's own row, so a save that changes nothing fails with «قبلاً استفاده شده» — this ticket makes the existing rules work on the update side while leaving registration untouched.

Back end only; verified by tests rather than a screen.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

Spec: [The username rule already in the codebase is broken for updates](../spec.md). Decision: [What changing a username costs](03-what-changing-a-username-costs.md).

- [ ] The shared username rules accept an optional user to ignore, and the uniqueness closure excludes that user's own row. The rules stay in one place — registration keeps calling the no-argument shape and its behaviour does not move.
- [ ] The reserved-name half of the rule applies on update exactly as on creation: nobody renames themselves onto a path the app routes.
- [ ] `support`, `help` and `arezoo` join the reserved list.
- [ ] Feature test: a user saves their own current username and it succeeds. This is the named regression.
- [ ] Feature test: renaming onto a name someone else holds fails.
- [ ] Feature test: renaming onto a reserved name fails. Together with the previous case this catches an ignore that swallows the whole uniqueness closure rather than just the user's own row — a wrong implementation that still passes the other three.
- [ ] Feature test: a freed username is immediately takeable by someone else. This is the one assertion pinning the domain rule that a released name is free at once.
- [ ] Feature test: an **unverified** user can submit the profile-information endpoint successfully, named for the middleware fact so the failure explains itself the day someone tidies `verified` onto the route.
- [ ] No test re-asserts Fortify's own behaviour, and no notification fake appears. No unit test on the rules trait.
