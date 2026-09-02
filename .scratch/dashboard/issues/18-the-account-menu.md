# 18 — The account menu in the header

**What to build:** A signed-in user opens a menu under their own avatar in the header and reaches their public page, their account screen and their inbox from anywhere in the app — **and can finally sign out**, which no page in the app allows today once a user is verified. An unverified user is told why two of the rows are unavailable rather than finding them missing.

**Scoped deliberately:** no unread badge, and no برداشت‌ها row. Both wait on decisions that are still open.

**Blocked by:** 13, 17 (the rows link to these pages).

**Status:** ready-for-agent

Spec: [The account menu](../spec.md). Decision: [The account menu in the header](06-the-account-menu-in-the-header.md), prototyped in commit `fce8acc`, now on `main`.

- [ ] The header's bordered user icon becomes a dropdown whose trigger is the user's own round avatar, falling back to a circle carrying the first letter of their name.
- [ ] The panel opens on a name and `@username` identity block, then the navigation rows with icons, a separator, then sign-out.
- [ ] Rows: صفحه من (the public profile), اطلاعات من (the account screen), پیام‌ها (the inbox), then sign-out. برداشت‌ها is added by the withdrawals ticket.
- [ ] **Sign-out is a `<Link>`, and this is not interchangeable.** The generated route helper already carries the POST method. All three candidates were probed in a real menu: a `<Link>` works, a programmatic post works, and an Inertia **`<Form>` does not** — the menu item's select unmounts the portal before the form submits. That is the shape a reader reaches for first, so it must be named explicitly in review.
- [ ] An unverified user sees an amber «ایمیلت هنوز تأیید نشده» strip between the identity block and the rows.
- [ ] For an unverified user, اطلاعات من stays **enabled** — the account screen no longer requires verification — and پیام‌ها renders disabled, greyed and skipped by keyboard navigation. Rows are disabled rather than hidden: a user who cannot see a section cannot learn it exists.
- [ ] **The layout gains a direction provider set to RTL.** The component library reads direction from that provider, **not** from the DOM's `dir` attribute, and defaults to left-to-right — without it, end-alignment flips the panel to the opposite edge of the viewport, clear of its own trigger. It goes in the layout, not on the menu, so the next popper-positioned component does not repeat the bug. This has been invisible until now because every existing use of the library in this app is side-agnostic.
- [ ] No burger and no breakpoint. The dropdown fits at 375px beside the logo and the landing page's anchors; the admin header needs a burger only because it renders six items inline.
- [ ] The existing header-nav mechanism is untouched and keeps meaning the current page's own anchors. The menu carries the sections from everywhere, so a second inline nav would be the same links twice with an active state to keep in sync.
- [ ] The verify-email notice keeps its own sign-out link even though the menu now offers one.
- [ ] Browser tests assert back-end truth — that sign-out ends the session — and never that the menu closed.
