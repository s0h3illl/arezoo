# Assemble the auth-pages spec

Type: task
Status: resolved
Blocked by: 01, 02, 03, 04, 06

## Question

Fold every resolved decision into `.scratch/auth-pages/spec.md`: per-page sections (Register, Forgot Password, Reset Password, Confirm Password, Verify Email) written as deltas on Login's card pattern, plus the server-side Persian plan and the Fortify config/User-model changes each page needs. This is the destination artifact — implementation remains a separate effort gated on the user's go.

## Answer

Done: [the spec](../spec.md) was published (Status: ready-for-agent) via `/to-spec`, synthesizing the charting session's decisions directly rather than working tickets 01–04 and 06 one at a time. Implementation still waits for the user's explicit go.
