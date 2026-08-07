# 01 — Admins are invisible to the users section

**What to build:** An admin using the panel can no longer see or act on another admin. Staff accounts are absent from the users list and unreachable by search, their detail page answers as though it does not exist, and blocking, editing, or setting the password of an admin is impossible — including by a crafted request from someone who knows the identifier. Ordinary users are unaffected: the list, its search, its pagination, and every action on a non-admin behave exactly as they do today.

The rule is expressed once, at route-model binding, so that the detail view, the edit and block route, and the set-password route all inherit it and no controller action can forget it. The users list applies the same exclusion to its own query.

The consequence is intended: no admin can be renamed, blocked, or given a new password through the panel by any means. Staff accounts are managed where admin status is granted — out of band. This mirrors the existing decision that admin status is ungrantable through the app.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [x] An admin does not appear in the users list.
- [x] Searching the users list by an admin's name or email returns no results.
- [x] The users list's reported total counts only non-admin users, and blocked non-admin users are still counted.
- [x] Requesting the detail view for an admin answers 404.
- [x] Updating an admin's name or email answers 404 and changes nothing.
- [x] Blocking or unblocking an admin answers 404 and changes nothing.
- [x] Setting an admin's password answers 404 and leaves their password unchanged.
- [x] Every one of those routes still behaves as before for a non-admin target, blocked or not.
- [x] The three existing users-list tests that assume admins are listed are rewritten — including the one whose name asserts that an admin sees every user, which is no longer a true statement about the domain, and the one that creates only the acting admin and would otherwise have no row left to assert against.
- [x] The existing access tests still pass unchanged: a signed-in non-admin and a guest each get a 404 from every admin route, rather than a 403 or a login redirect.
- [x] `CONTEXT.md`'s Admin entry is corrected — an admin sees every user *other than an admin* — and records that admins are not themselves managed from the panel.
- [x] An ADR records the decision and its trade-off: one rule enforced in one place, paid for with all panel control over staff accounts, and the reason a future reader hitting a 404 on an account they can see exists is not looking at a bug.
- [x] The browser smoke tests for the users list and user detail still pass. They act only on non-admin users, so they should need no changes — if one does, that is a signal the rule reached further than intended.

## Comments

**2026-08-07 — implemented on `seed-failed-payments`.** Every checklist item is done. The rule lives in one `Route::bind('user', ...)` in `AppServiceProvider`, registered globally as the spec sanctions, plus an `excludingAdmins()` scope on `User` that the list query shares with the binding. `CONTEXT.md`'s Admin entry is corrected and ADR-0008 records the trade-off. The two browser smoke tests for the users list and user detail passed unchanged, as predicted.
