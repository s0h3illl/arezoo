# 05 — Sharing a profile, and the toast that confirms it

**What to build:** The share button on each card, and the first toast in the app.

Sharing hands the link to the operating system's own share sheet through `navigator.share`, so a reader can send it through whatever app they already use. Where that API does not exist the link goes to the clipboard instead. That fallback is not an edge case: `navigator.share` is absent on desktop Linux and in Firefox, so the clipboard path is the one that runs during development and in the browser tests.

Both paths confirm themselves — «لینک به اشتراک گذاشته شد» after a native share, «لینک کپی شد» after a copy. A dismissed share sheet raises an `AbortError`, which is a person changing their mind rather than a failure, and says nothing at all.

The behaviour lives in a `useShare.ts` composable so that no page owns it and the target can move later in one place.

**Every card shares the profile URL**, `/u/{username}`. There is no wish URL yet — the wish detail page is a later piece of work. The accepted consequence is that every share button on a profile sends the same link; when that page lands, only the composable changes.

Toasts are reka-ui primitives. reka-ui has no global `toast()` function, so this ticket writes the small imperative wrapper over a reactive list once, with the viewport mounted in `AppLayout` so any later page can raise one without repeating the setup.

**Blocked by:** 02 — The profile page and its wish grid.

**Status:** ready-for-agent

- [x] A `useShare.ts` composable shares a URL via `navigator.share` when it exists
- [x] Where `navigator.share` is unavailable, the URL is written to the clipboard instead
- [x] A successful native share raises «لینک به اشتراک گذاشته شد»
- [x] A clipboard copy raises «لینک کپی شد»
- [x] A dismissed share sheet raises nothing at all
- [x] Any other failure raises a Persian error toast rather than failing silently
- [ ] Every card's share button sends the profile URL, absolute and including the scheme and host
- [x] The share button is visible to the owner, to a signed-in visitor, and to a guest alike
- [x] Toasts use reka-ui's toast primitives, with the viewport mounted once in `AppLayout`
- [x] A toast is dismissible and disappears on its own
- [x] Toasts render RTL and in Persian, consistent with the rest of the app
- [x] Pint clean, larastan clean, `bun run types:check` clean

## Comments

**Reverted to a `#` placeholder.** The composable was briefly wired to
`profile.url(username)`, which meant drilling a `user` prop into every
`WishCard` just to build a link. That link was premature: there is no page
for it to point at yet, only the profile page it happened to reuse. Sharing
now sends `'#'`, and `WishCard` no longer needs `user` at all. Re-close this
item once there's a real page to send — a wish's own, most likely — by
changing `shareProfile()` alone.

**What the browser tests can and cannot reach.** The browser these tests drive
reports `navigator.share` as absent — exactly as the ticket predicted for desktop
Linux and Firefox — so every test takes the clipboard path. That clipboard then
refuses the write: a headless browser grants no clipboard permission, where a
real one grants it to the tab the reader just clicked in. So the branch the tests
actually walk is the failure, and `ShareProfileTest` is named for it: a share that
cannot be delivered says so rather than doing nothing.

That leaves the two success messages, «لینک کپی شد» and «لینک به اشتراک گذاشته
شد», unasserted. Reaching either would mean replacing `navigator.share` or the
clipboard with something written in the test, which is the stubbing the spec
already ruled out — it would test the replacement rather than the app. The
plugin exposes no way to grant a browser permission either; only geolocation
sets one, and there is no general option for it.

The cost of that is worth naming: the failure test is pinned to what this
harness refuses, not to a requirement. If a later plugin grants the clipboard,
it goes red — and the fix is to change the one message it expects to «لینک کپی
شد», which is the assertion everyone would rather have had.

Auto-expiry is likewise left to reka-ui, whose timer closes the toast the same
way its dismiss button does. The dismiss test covers the one half of that which
is ours: the closed toast leaving the list, without which every toast ever raised
would stay in it invisibly.
