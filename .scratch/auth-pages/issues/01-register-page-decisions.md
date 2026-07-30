# Register page decisions

Type: grilling
Status: resolved

## Question

What does the Register page ask for and where does it send people? Fields (`CreateNewUser` currently validates name/email/password — password confirmation field? terms checkbox?), Persian copy (headline/subline/labels/CTA in Login's voice), cross-links (back to login), and the post-register destination once email verification is on (straight to the Verify Email notice?).

## Answer

Resolved by fast-forwarding into [the spec](../spec.md) (via `/to-spec`): fields are name/email/password/password-confirmation (matching the existing user-creation action), no terms checkbox, a link back to login, and post-register redirects to the Verify Email notice via a custom register response.
