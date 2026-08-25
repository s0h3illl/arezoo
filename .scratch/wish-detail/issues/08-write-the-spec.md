# 08 — Write the spec

Type: task
Status: resolved
Blocked by: 01, 02, 03, 04, 05, 06, 07

## Question

Nothing left to decide — the last step is writing it down. Produce `.scratch/wish-detail/spec.md` in the shape of `.scratch/edit-a-wish/spec.md`: Problem Statement, Solution, User Stories, Implementation Decisions, Testing Decisions, Out of Scope, Further Notes. Read that file first and match its register — it argues for its decisions rather than listing them, and names the thing that would otherwise be re-litigated.

Then split it into numbered issues under `.scratch/wish-detail/issues/`, following `docs/agents/issue-tracker.md` and the triage roles in `docs/agents/triage-labels.md`, each with a `Status:` line and a checklist an agent can work to.

Carry across, from the tickets rather than from memory:

- Every decision on this map, each traceable to the ticket that made it.
- The **Testing Decisions** section. This repo tests everything it ships, and two of its rules bite here: the Pest browser cannot post multipart, and a browser assertion must read back-end truth rather than watch the DOM. A masked contributor's details **not** reaching the page source is a feature test, and the most important one on the page — write it as the assertion that stops the leak coming back, the way `edit-a-wish` did for the shared user prop.
- The **Out of Scope** section, from this map's own — taking money, lists, re-specifying the edit modal, the typed-confirmation delete — plus whatever the tickets ruled out on the way.
- The dependencies on work this effort does not own: `.scratch/contributions/issues/05` still owes user soft-deletes and a `withTrashed()` contributor relation, and the contribute effort does not exist yet.

Whether the fog in the map's **Not yet specified** — the contribution `message`, the deleted contributor, meta tags, the contribute handoff — has become a decision, a ticket, or an explicit Out of Scope line must be settled here. Nothing may still be foggy when the map closes.

## Answer

[`spec.md`](../spec.md) is published, `ready-for-agent`, in `edit-a-wish`'s shape. Two implementation tickets carry it: [The wish page's data](09-the-wish-pages-data.md) (route, access scope, the public `ContributionResource`/`ContributionCollection` and its masking) and [The wish detail page](10-the-wish-detail-page.md) (the Vue page, reaching it from the profile grid, and the update-redirect fix that keeps an edit on the page it started from).

**Every fog item settled, none left open:**

- **The contribution `message`.** Already decided at [The server shape for the wish page](05-the-server-shape.md) — no `message` key on the public resource at all. Carried into the spec's Implementation Decisions; no new ticket needed.
- **The deleted contributor.** Decided at [What a contributor row shows](03-what-a-contributor-row-shows.md), not buildable: blocked on `.scratch/contributions/issues/05`. Recorded in the spec as an Out of Scope line with a code-comment marker in ticket 09, rather than a ticket of its own — there's nothing to ticket until the dependency lands and the shape it should take is already on paper.
- **Meta tags.** No precedent anywhere in the app for any page; ruled Out of Scope as a platform-wide gap, not one specific to this page.
- **The contribute handoff.** Written as a Further Notes paragraph in the spec — the funding card's CTA-less shape is the contract, and where the button goes is `.scratch/contribute/`'s call once that effort has a server behind it, not this one's to pre-slice.
- **Staleness of the relative-time string**, surfaced by research but never in the original fog list — added to Out of Scope with the same reasoning research gave: nothing in the app refreshes a date today, and this page doesn't start.

**Two things found while writing that no ticket had caught, both settled rather than left for whoever builds this:**

- **The buy-link row.** The design draws one (`wish.link`), no ticket 00–07 mentions it, but `resources/js/types/wish.ts`'s own comment already names this page as `purchase_link`'s destination, and `.scratch/edit-a-wish/spec.md`'s Out of Scope names this page by name. Settled as a direct render of an existing field — not controversial enough to have needed its own grilling ticket.
- **The edit-redirect bounce.** `WishController::update()` hard-codes a redirect to the profile, which reads fine from the grid and wrong from the wish's own page — an owner fixing a typo here would be bounced off the page they were looking at. Changed to `redirect()->back()`, with `destroy()` left alone since deleting is meant to leave the page. A one-line fix, recorded in the spec and given its own checklist item and test in ticket 10.

**One thing found while writing that the map had gotten wrong**, corrected rather than carried forward: [Who may read a wish page](02-who-may-read-a-wish-page.md)'s "pre-existing leak" in `ProfileController` no longer exists — `Route::bind('username', ...)` already applies `excludingBlocked()`, and `tests/Feature/ProfileTest.php`'s `'a blocked user has no profile'` passes today. The spec notes this explicitly so ticket 09 doesn't re-fix an already-fixed door; its own work is only the analogous `wish` binding.
