# Localization approach decision

Type: grilling
Status: resolved
Blocked by: 05

## Question

Given the findings from [Persian server-message options](05-persian-server-messages-research.md): package vs hand-rolled `lang/fa` files, whether `APP_LOCALE` flips to `fa`, and how the two notification emails (reset password, verify email) get their Persian templates.

## Answer

Resolved ahead of its blocker by fast-forwarding into [the spec](../spec.md) (via `/to-spec`): hand-rolled `lang/fa` files scoped to what the auth flows surface (no new composer dependency), `APP_LOCALE=fa` with `en` fallback, and Persian emails via the framework's notification customization callbacks with an RTL-friendly mail layout. The research findings on `research/persian-auth-messages` refine the mechanics — the implementer should consult them and flag back if they contradict this.
