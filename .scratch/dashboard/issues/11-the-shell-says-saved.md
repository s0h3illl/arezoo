# 11 — The shell says «saved»

**Status:** closed — won't do. Superseded by the amendment on [What the shell does with a server `status`](10-what-the-shell-does-with-a-server-status.md).

This ticket existed to centralise server success messages in `AppLayout` and move them off the shared `status` prop. That is not happening. The standing rule is: **wherever Fortify forces a `status`, use `status` and change nothing; where the endpoint is our own code, raise a toast.**

Under that rule this ticket has no deliverable. Every save the dashboard map adds runs through a Fortify endpoint, so every one reports through `status`, rendered on the page that submitted — carried as checklist items on [The account screen](13-the-account-screen.md), including the Persian mapping for Fortify's two English sentinels. Nothing is shared between pages, so nothing is blocked on this.

The toast half of the rule needs no ticket either: it is one `raiseToast()` call in whichever of our own pages saves, and this map adds none.

Nothing here is deferred. There is no follow-up.
