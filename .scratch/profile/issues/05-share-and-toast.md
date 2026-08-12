# 05 — Sharing a profile, and the toast that confirms it

**What to build:** The share button on each card, and the first toast in the app.

Sharing hands the link to the operating system's own share sheet through `navigator.share`, so a reader can send it through whatever app they already use. Where that API does not exist the link goes to the clipboard instead. That fallback is not an edge case: `navigator.share` is absent on desktop Linux and in Firefox, so the clipboard path is the one that runs during development and in the browser tests.

Both paths confirm themselves — «لینک به اشتراک گذاشته شد» after a native share, «لینک کپی شد» after a copy. A dismissed share sheet raises an `AbortError`, which is a person changing their mind rather than a failure, and says nothing at all.

The behaviour lives in a `useShare.ts` composable so that no page owns it and the target can move later in one place.

**Every card shares the profile URL**, `/u/{username}`. There is no wish URL yet — the wish detail page is a later piece of work. The accepted consequence is that every share button on a profile sends the same link; when that page lands, only the composable changes.

Toasts are reka-ui primitives. reka-ui has no global `toast()` function, so this ticket writes the small imperative wrapper over a reactive list once, with the viewport mounted in `AppLayout` so any later page can raise one without repeating the setup.

**Blocked by:** 02 — The profile page and its wish grid.

**Status:** ready-for-agent

- [ ] A `useShare.ts` composable shares a URL via `navigator.share` when it exists
- [ ] Where `navigator.share` is unavailable, the URL is written to the clipboard instead
- [ ] A successful native share raises «لینک به اشتراک گذاشته شد»
- [ ] A clipboard copy raises «لینک کپی شد»
- [ ] A dismissed share sheet raises nothing at all
- [ ] Any other failure raises a Persian error toast rather than failing silently
- [ ] Every card's share button sends the profile URL, absolute and including the scheme and host
- [ ] The share button is visible to the owner, to a signed-in visitor, and to a guest alike
- [ ] Toasts use reka-ui's toast primitives, with the viewport mounted once in `AppLayout`
- [ ] A toast is dismissible and disappears on its own
- [ ] Toasts render RTL and in Persian, consistent with the rest of the app
- [ ] Pint clean, larastan clean, `bun run types:check` clean
