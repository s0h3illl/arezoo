# 17 — The inbox at `/dashboard/messages`

**What to build:** An owner reads the messages contributors left on their wishes — every paid contribution that carries one, newest first, loading more as they scroll. A contributor who chose to stay hidden has their message shown with their name withheld, so a note is never silently discarded.

**Blocked by:** 16 (the rule this page implements is recorded first).

**Status:** ready-for-agent

Spec: [The inbox](../spec.md). Decision: [What the inbox shows](04-what-the-inbox-shows.md).

- [ ] `/dashboard/messages` on `auth` + `verified`.
- [ ] The list carries every **paid** contribution to the reader's own wishes whose message is not null, **at all three visibilities**. Charting filtered `hidden` out; that is reversed.
- [ ] A hidden contributor is masked exactly as a contributor row masks one — «ناشناس», the «؟» circle, no avatar — and the amount still shows, because Visibility governs identity and never money.
- [ ] Ordered by when the money landed, descending, with the row id breaking a same-second tie. **Not** by creation time: that records when a gateway attempt began, and the row displays the settled time, so ordering by creation could put a row above an older-looking date.
- [ ] Infinite scroll at **15** rows per page — the app's existing list mechanism, but not its existing page size: 7 is sized for a card grid and a small contributor row, and a message row is several lines tall.
- [ ] The message renders **in full** — no clamp, no "more" link, no scroll box, wrapping on its own whitespace the way a wish description already does. Length is defended where the message is written, not where it is read.
- [ ] The wish title is plain text and **never** links, not even for a live wish — so a trashed wish needs no marker and no special copy. Accepted consequence: an owner who wants to open the wish goes to their own profile.
- [ ] A **new** message resource carrying the masking state, the contributor block, the message, the wish title, the amount and the settled time. Masked rows carry «ناشناس» and a null avatar as **present keys**, never absent ones.
- [ ] **The existing contribution resource is not touched.** The wish page's spec guarantees no message key anywhere in it; adding a conditional one would make that page's payload depend on a condition not firing.
- [ ] No contributor id reaches the browser, so a masked row cannot be traced back to a user.
- [ ] One empty state covering both "nobody contributed" and "nobody wrote". The two-state version was declined — it costs a second count query on every load to phrase an absence.
- [ ] «کاربر حذف‌شده» for a departed contributor is **decided but not built**: users are not soft-deletable yet. It is a third state, distinct from «ناشناس» — reusing the anonymous copy would tell the owner the contributor chose to hide when the account is simply gone. Do not let it hold this page.
