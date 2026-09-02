# 08 — Write the spec

Type: grilling
Status: open
Blocked by: 01, 02, 03, 04, 05, 06, 07, 09, 10

## Question

Assemble `.scratch/dashboard/spec.md` from this map's resolved tickets, in the shape of `.scratch/wish-detail/spec.md`: Problem Statement, Solution, User Stories, Implementation Decisions, Testing Decisions, Out of Scope, Further Notes. Mark it `ready-for-agent` and carry it into numbered build tickets.

Things this ticket must not lose:

- **The three amendments to other efforts.** Cut stories 8–10 from `.scratch/balance-and-withdrawals/spec.md` per [The withdrawals page](07-the-withdrawals-page-against-the-balance-spec.md); add the email-change form to `auth/VerifyEmail.vue` per [How an email change is carried](02-how-an-email-change-is-carried.md). Edit those files, do not merely describe the edit.
- **The accepted risk, stated plainly.** No password confirmation anywhere, so an open session can change the Sheba and withdraw to it. It belongs in Further Notes as a decision taken, with the reason — avoiding a dependency on the unbuilt `.scratch/auth-pages/issues/14-confirm-password-page.md` — so review does not re-litigate it.
- **The vocabulary.** `CONTEXT.md` governs: Owner, Contribution, Contributor, Balance, Available, Held, Withdrawal, Toman, Profile, Username, Visibility. Held money is never "pending"; a withdrawal is never a "payout" or a "transaction"; and this section is never a "profile" — `/u/{username}` is the profile. Per memory, an unauthenticated visitor is a "guest", not a "signed-out visitor".
- **The glossary entries this effort owes.** «Dashboard» is a new term and `CONTEXT.md` does not have it. Decide whether `messages_seen_at` introduces one too, and whether Visibility's entry needs amending now that the inbox is a second reader of the message rule.
- **The testing seam**, which the map left foggy on purpose. Feature tests at the HTTP boundary are the house style, but the avatar upload cannot be browser-tested (the Pest browser parses urlencoded only) and the magic-link email change has a signed-URL surface neither prior effort covers. Per memory: browser tests assert back-end truth — click, type, read the database — and never assert that a `reka-ui` menu closed.
- **The success-feedback rule.** Per the amendment on [What the shell does with a server `status`](10-what-the-shell-does-with-a-server-status.md): wherever Fortify forces a `status`, `status` is used unchanged; where the endpoint is ours, a toast. Every save this map adds is a Fortify one, so the dashboard raises no toast — the account screen shows `status` and maps its two English sentinels to Persian in the Vue file. Record in Implementation Decisions that the centralised flash-data design was considered and overruled, so review does not re-propose it, and that the shared-prop history behaviours are accepted rather than solved. The `auth-pages` amendment list is **one** page, not four.

- **What is deliberately absent.** The map's Out of scope section, carried across so a reader of the spec alone knows what was ruled out and why.

Then create the build tickets. `auth-pages` and `wish-detail` both did this, and both kept them small enough for one agent session each.

## Comments

**A draft of the spec exists at `.scratch/dashboard/spec.md`, written while this ticket was still blocked.** It consolidates the seven resolved tickets — Problem Statement, Solution, 12 User Stories, Implementation Decisions across routes / account screen / username rule / email / avatar / inbox / account menu / success feedback, Testing Decisions, Out of Scope, Further Notes. It is marked **`draft`, not `ready-for-agent`**, and carries an **Open Decisions** section naming what is missing.

**Deliberately not done, because both depend on unresolved decisions:**

- **No user stories or implementation decisions for `/dashboard/withdrawals`** — a third of the section. [The withdrawals page](07-the-withdrawals-page-against-the-balance-spec.md) is open and unclaimed, and nothing here guesses at its answers.
- **The `.scratch/balance-and-withdrawals/spec.md` amendment is NOT made.** This ticket says to edit that file rather than describe the edit, but the precise edit *is* ticket 07's answer. The spec records the obligation and says explicitly that it is outstanding.
- **`messages_seen_at`'s glossary question is left open**, since [What the unread badge costs](05-what-the-unread-badge-costs.md) decides what the stamp means. The menu's dot-and-count presentation is already settled and is written up; what feeds it is not.
- **Build tickets 11–18 are written** and cover every settled surface; see the spec's Build Tickets table. **Two are still owed**: the unread badge and the withdrawals page, both blocked on the open decisions above. Ticket 18 (the account menu) is deliberately scoped to exclude the badge and the برداشت‌ها row, so the withdrawals ticket adds the row when it lands.

**Everything else the ticket's must-not-lose list names is in the draft:** the accepted no-password-confirmation risk with its reason, the vocabulary (Dashboard never called a profile; Held never «pending»; guest for signed-out), the one `auth-pages` amendment, the `CONTEXT.md:38` rewrite and its owed ADR, the contribute-effort constraints, the testing seam including the untestable canvas downscale, the success-feedback rule and its accepted costs, and the Out of scope section carried across.

Finishing this ticket means resolving 07 and 05, then filling the withdrawals sections, making the balance-spec edit, flipping the status to `ready-for-agent`, and cutting the build tickets.
