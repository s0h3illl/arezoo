# Email verification rollout

Type: grilling
Status: resolved

## Question

Enabling `Features::emailVerification()`: which routes/actions require a verified email (`verified` middleware) — everything authenticated, or only sensitive actions like publishing a Wish or making a Contribution? And what does the Verify Email notice page do: resend button and its throttle feedback, logout link, Persian copy. Includes `User implements MustVerifyEmail`. This ticket decides; it does not implement.

## Answer

Resolved by fast-forwarding into [the spec](../spec.md) (via `/to-spec`): verification gates only mutating domain actions — publishing a Wish, making a Contribution — never browsing; no routes exist to gate yet, so the policy binds future routes. The notice page shows the target address, a resend button with throttled Persian feedback, and a logout link; already-verified visitors are redirected into the app.
