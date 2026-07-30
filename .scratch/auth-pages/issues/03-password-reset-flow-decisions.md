# Forgot & reset password flow decisions

Type: grilling
Status: resolved

## Question

The Forgot Password and Reset Password pages: Persian copy for both, the post-submit state on forgot (status flash — anti-enumeration "always success" wording or honest errors?), expired/invalid token UX on reset, and where a successful reset lands (Fortify's default redirect to login with a status flash, or something else).

## Answer

Resolved by fast-forwarding into [the spec](../spec.md) (via `/to-spec`): Fortify's standard behavior throughout, Persian-ized — unknown email gets an honest validation error (no anti-enumeration masking), success gets the status flash, expired/invalid tokens get a clear error, and a completed reset lands on login with a Persian flash (no auto-login).
