# 16 — A message is no longer narrower than identity

**What to build:** The domain rule that governs who reads a contribution's message is rewritten, and the reversal is recorded as a decision rather than buried in a page. Today the language says a message stays unread under `hidden`; the inbox is about to show it. This ticket makes the written rule and the built behaviour agree — **before** the behaviour lands.

Documentation only. No code.

**Blocked by:** None — can start immediately.

**Status:** done

Spec: [Amendments this effort owes other efforts](../spec.md). Decision: [What the inbox shows](04-what-the-inbox-shows.md).

- [x] The glossary's Visibility entry is rewritten. The new rule: the wish's owner reads every message on a paid contribution to their wish, and Visibility governs whose **name** sits above it, never whether it is read.
- [x] A new ADR sits beside the one stating that Visibility hides identity and never amount, following its shape — it records the same kind of "this looks like it should be symmetric and is not" finding.
- [x] The ADR states the weakness plainly rather than glossing it: a message is free text, so masking the name does not mask the body. Under this decision `hidden` means "my name is not printed", not "the owner learns nothing" — a label-level mask, not a guarantee.
- [x] It states why the trade is accepted anyway: the alternative silently destroys what a contributor wrote, with nothing in the app telling them so. Between a weakened promise and a discarded message, the weakened promise is the one both parties can see.
- [x] A glossary entry for «Dashboard» is added, noting that the public page owns the word «Profile» and that this section is never called one.
