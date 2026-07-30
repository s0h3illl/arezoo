# Confirm password page decisions

Type: grilling
Status: resolved

## Question

The Confirm Password screen guards sensitive actions (today: passkey confirmation via `passkeys.confirmPassword`). Persian copy, and how it presents — a dedicated page render at GET `user/confirm-password` in the Login card pattern, and what "back" means from there (return to intended URL is handled by Fortify; what does the user see and click?).

## Answer

Resolved by fast-forwarding into [the spec](../spec.md) (via `/to-spec`): a dedicated page (not a modal) in the Login card pattern; Fortify's intended-URL handling returns the user to the interrupted action, and a wrong password shows a Persian inline error.
