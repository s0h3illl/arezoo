# Admins are invisible to the users section

An admin is not one of the platform's users as far as the panel is concerned. Admins are absent from the users list and unfindable by its search, and every admin route that takes a user — the detail view, the edit and block route, the set-password route — answers 404 for an admin target, including for a crafted request from someone who knows the identifier.

The rule is enforced once, at route-model binding: the `user` route parameter resolves only non-admin users and 404s otherwise, so no controller action can forget it. The list applies the same exclusion to its own query. The binding is registered globally rather than scoped to the admin group — the parameter appears only in admin routes today, and a route that binds it later inherits the rule rather than quietly opting out of it.

The trade-off is the whole decision. One rule in one place is paid for with all panel control over staff accounts: no admin can be renamed, blocked, or given a new password through the panel, by any means. Staff accounts are managed where admin status is granted — out of band, in a seeder or the database. That mirrors the existing decision that admin status is ungrantable through the app, and it means panel access and the ability to change panel access are not the same power: a careless or compromised admin session cannot lock the platform's operators out.

A future reader who opens an account they can see exists and gets a 404 is not looking at a bug. That account is an admin, and this is the rule answering.

## Considered Options

- A guard in each controller action — rejected as the option this one exists to replace. Four actions today, each free to forget the check, and the next screen added to the section starts out forgetting it.
- Hide admins from the list only — rejected as cosmetic. The list is one way in; the detail URL, the block request, and the set-password request are three more, and a rule defeated by typing an identifier is not a rule.
- A 403 for an admin target instead of a 404 — rejected for the reason the section itself answers 404: a 403 confirms the account is there and merely out of reach. The panel's one answer for "not yours to see" is the answer a made-up URL gives.
- Let an admin manage other admins but not themselves — rejected. It keeps every risk this decision removes (one compromised session can still block or take over every other operator) in exchange for convenience the out-of-band path already covers.

## Consequences

- Any future admin screen reading users must exclude admins in its own query, the way the list does. The binding covers routes that take a user; it cannot cover a query nobody bound.
- An explicit binding replaces implicit binding for the parameter outright, so a route that wants a custom key (`{user:email}`) or a `missing()` handler no longer gets one from the framework. Either would have to be built into the binding itself.
- A platform user count taken from the panel counts non-admins, and will differ from `users` table's row count by the number of staff accounts.
- Granting, revoking, or repairing a staff account is a database or seeder operation. If that ever becomes too painful to live with, this decision is the thing to revisit — not to patch around with a controller-level exception.
