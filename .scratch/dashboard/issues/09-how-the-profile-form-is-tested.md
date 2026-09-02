# 09 — How the profile form is tested

Type: grilling
Status: resolved
Blocked by: none

## Question

Rewritten. The ticket used to ask how a signed-URL email flow was tested; [How an email change is carried](02-how-an-email-change-is-carried.md) has since reverted to stock Fortify, and [What changing a username costs](03-what-changing-a-username-costs.md) has put name, username, email, avatar and bio on one endpoint. So the surface to test is a single form on `PUT /user/profile-information`, not a two-request flow.

The approach is settled by precedent: feature tests at the HTTP boundary plus browser smoke tests, as `.scratch/balance-and-withdrawals/spec.md` and `.scratch/auth-pages/spec.md` both do. [How an avatar is carried](01-how-an-avatar-is-carried.md) settled its own half, including the known gap that the canvas downscale is covered by nothing, because the Pest browser cannot POST multipart.

Decide what the spec commits to here:

- **How much of Fortify's own behaviour is re-asserted.** The write, the nulled `email_verified_at` and the verification mail all come from vendor code that has its own tests. Decide whether `Notification::fake()` plus an assertion that the notification went to the *new* address earns its place, or whether the app only tests the rules it added.
- **The no-op save.** [What changing a username costs](03-what-changing-a-username-costs.md) found that `validateUsernameIsFree()` counts the user's own row, so submitting the form unchanged fails today. That is a regression test with a name — decide it exists and where it lives.
- **What the username rules owe on update**, beyond the no-op: a rename onto another user's name, a rename onto a reserved name, a rename that differs only in case, and the freed name being immediately takeable by someone else — the one assertion that pins the `CONTEXT.md` commitment to a test.
- **Whether `usernameRules()` gaining an ignore parameter needs a unit test**, or whether the feature tests through the endpoint cover it. The trait has two callers with different arguments, which is the argument for testing it directly.
- **The two entry points.** `/dashboard` and `auth/VerifyEmail.vue` post the same endpoint, and the second is reachable only by an unverified user. Decide whether that is one test parameterised over the entry point or two, and assert that the endpoint is *not* behind `verified` — the middleware fact the whole recovery path rests on.
- **What the browser tests cover.** Click through the account screen and read the database, the house standard. Decide which fields that covers and whether the email change is among them, given the verification mail cannot be opened.
- **What existing tests need.** `UpdateUserProfileInformation`'s current behaviour is unchanged by the revert, so its tests should still pass — confirm that rather than assume it, and note the Pest rules forbid deleting a test without approval.

## Answer

**Fortify is never re-asserted.** No test covers the write, the nulled `email_verified_at`, the verification mail, or the validation of `name`/`email` — that is vendor code with its own suite. `Notification::fake()` does not appear anywhere in this spec, and the email branch gets no feature test. The app tests only the rules it added.

**Two seams, each with a job.** The app-added rules are asserted at the HTTP boundary, as `tests/Feature/Auth/RegisterTest.php` already does for the same username rules; the browser seam is reserved for real user interactions. Driving a validation matrix through a browser would be ten page loads to prove what one request proves.

**`/dashboard` drops `verified`.** This amends charting, [How an email change is carried](02-how-an-email-change-is-carried.md) and [The account menu in the header](06-the-account-menu-in-the-header.md). The account screen runs on `auth` only; `/dashboard/messages` and `/dashboard/withdrawals` keep `auth` + `verified`. The dropdown enables اطلاعات من for every signed-in user and keeps the other two rows disabled until verification. The recovery path is therefore no longer a workaround — an unverified user reaches the real account screen from the header.

**`auth/VerifyEmail.vue` keeps its own email form anyway** — decided deliberately, with the duplication understood: two forms, two wirings, two error displays, and they must be kept in step. It buys the fix without leaving the notice page, and it costs the third browser test below.

### The five feature tests — the update-side username matrix

Only what is different *because a row already exists*. Shape, length, charset and Persian error copy stay pinned once at the register seam and are not repeated.

1. **The no-op save succeeds** — the user submits their own current username. The named regression from [What changing a username costs](03-what-changing-a-username-costs.md): `validateUsernameIsFree()` counts the user's own row today.
2. **A case-only self-rename succeeds** — `sara` → `Sara` saves and stores `sara`. The no-op's sharp edge: the ignore must compare against the user's row, not the literal string.
3. **A rename onto a name someone else holds fails.**
4. **A rename onto a reserved name fails.** 3 and 4 look like duplicates of register, but they are the two that catch the obvious wrong implementation — an ignore that swallows the whole uniqueness closure rather than just the user's own row still passes 1, 2 and 5.
5. **A freed name is immediately takeable** — sara renames, reza takes `sara`, it sticks. The one assertion pinning `CONTEXT.md:29`.

### The sixth feature test — the middleware fact

An **unverified** user PUTs `user-profile-information.update` and succeeds. Confirmed today by `route:list -v`: the route carries `web` and `auth:web`, no `verified`. Named for the fact, so the failure explains itself the day someone tidies `verified` onto the route.

The ticket's "one test parameterised over the entry point, or two?" **dissolves at this seam**: there is one route, and a feature test sends a request, not a page. Both entry points produce a byte-identical request, so parameterising over them would be two passes and zero coverage. The entry points are real only at the browser seam, where two different components are loaded.

### No unit test on `usernameRules()`

The trait is the app's own code, not Fortify's — an extraction shared by `CreateNewUser` and `UpdateUserProfileInformation` — so the no-vendor-tests rule does not settle it. Decided on its merits: what the method *returns* is a rule array and a closure, and asserting its contents is asserting implementation, brittle to any harmless reordering. What matters is the closure's behaviour, which is meaningless without a database. Both call shapes already run for real through an endpoint: `RegisterTest.php` drives the no-argument shape, the five tests above drive the ignore shape. `tests/Unit/` holds only `UserTest.php` and the scaffold; the repo has no habit of unit-testing traits. Revisit when a third caller appears, not before.

### The five browser tests

House standard, per `tests/Browser/EditWishTest.php`: visit, fill, click, then read the database.

1. **The account screen saves** — name, username and bio filled, saved, all three on the row. One page load, three fields.
2. **The email change on the account screen** — the row holds the new address and `email_verified_at` is null. The mail is never opened; the Pest browser cannot. This stops at database truth.
3. **The verify-notice form** — an unverified user on `auth/VerifyEmail.vue` fills the email, saves, the row changes. This test exists only because the duplicate form was kept; test 2 says nothing about whether the second form is wired.
4. **A validation error renders** — a username someone else holds is typed, saved, and the error appears on the page. The HTTP seam proves the rule fires; this is the only thing proving the user ever sees it, and the only proof the form displays errors at all.
5. **Smoke on `/dashboard`** — `assertNoJavaScriptErrors` plus the Persian copy, matching `tests/Browser/Auth/VerifyEmailSmokeTest.php`.

**Not tested, deliberately:** the avatar, in either seam — the canvas downscale is the known gap from [How an avatar is carried](01-how-an-avatar-is-carried.md), unreachable because the Pest browser cannot POST multipart; and the password form, stock Fortify on its own endpoint.

### The existing-tests bullet resolves as a fact, not a decision

`grep` over `tests/` for `profile-information` and `UpdateUserProfileInformation` returns **no files**. Nothing tests this endpoint today, so there is nothing to keep passing and nothing to delete — the Pest no-deletion rule never arises.

## Comments

**Amended by [What the shell does with a server `status`](10-what-the-shell-does-with-a-server-status.md), then un-amended by that decision's own amendment.** The ticket briefly moved success feedback to a shell-level mapping on Inertia flash data, which would have made it feature-testable at the HTTP boundary and asserted once against the layout. That is reversed. `status` is Fortify's and stays; the account screen's forms both post to Fortify endpoints, so they report through `status` and raise no toast.

- Success feedback is asserted **on the account screen, in the browser**, as a rendered element — there is no flash payload at the HTTP boundary and no shell mapping to assert once. This ticket's five-test budget grows by one browser assertion, not by a feature test.
- There is **no error toast** to test. A failed save shows its inline field error, which the username-taken test already covers.
- Everything else this ticket decided stands unchanged.
