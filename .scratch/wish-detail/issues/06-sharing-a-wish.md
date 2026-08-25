# 06 — Sharing a wish

Type: grilling
Status: resolved
Blocked by: none

## Question

`WishCard.vue` has a share control today. It is labelled `aria-label="اشتراک لینک پروفایل"` and calls `shareProfile()` — a share button sitting on a wish that copies the link to the whole profile. That was the only honest thing it could do while a wish had no address of its own. It now has one.

- **Does the card's share button change** to share the wish it sits on? It is the obvious reading of a control on a card, and it is a change to a shipped component with a shipped toast, so it wants to be deliberate rather than incidental.
- **Does the profile keep a way to share the profile** if the card's button stops doing it? Check what else on the profile shares today before deciding anything is being taken away.
- **Does the detail page get its own share control**, and where — the design draws none, but the page exists to be sent to people, which is the whole reason it has a URL.
- **What `useShare` already does.** Read `resources/js/composables/useShare.ts` before deciding: whether it wraps the Web Share API or copies to the clipboard, and what its toast says, both constrain what sharing a wish can look like.
- **The copy.** Whatever the toast says now was written about a profile. A wish needs its own wording, and the labels and `aria-label`s go stale with it.

## Answer

Two of this ticket's premises were wrong, and checking them changed the shape of the answer.

**The toast copy was never about a profile.** «لینک به اشتراک گذاشته شد», «لینک کپی شد» and «نشد لینک رو بفرستیم، دوباره امتحان کن» all say *link* and nothing more, so they carry over to a wish untouched. The only profile-specific string in the whole feature is `WishCard`'s `aria-label="اشتراک لینک پروفایل"`. There is no copy rewrite here, just one label.

**`shareProfile()` has exactly one call site** — the card button itself. Nothing on `Profile.vue` shares a profile; the card's button *was* the app's entire share surface. So pointing it at the wish does not move profile-sharing somewhere else, it ends it.

**The card's button shares the wish it sits on.** A control on a card shares that card's thing, and the reason it ever pointed at the profile — a wish with no address — is gone. [The destination and the shape of the page](00-charting.md) already fixed its position: the cover and title become the `wishes.show` links, and the footer's edit, share and delete controls stay buttons outside that anchor. Only the target and the label change.

**Profile sharing is dropped, not relocated.** `shareProfile()` is deleted rather than rehomed. The thing people send each other is a specific wish, and a profile is reachable from any wish page's «برگشت به پروفایل» once it has been sent — while its own URL is in the address bar of anyone already looking at it. This is a deliberate reduction in surface: the app goes from one share control that shared the wrong thing to one that shares the right one.

**The detail page gets no share control of its own.** The design draws none and none is added. The page is arrived at by URL, so the URL is already in front of whoever is reading it.

> **Recorded consequence.** Sharing a wish is therefore only possible from the profile grid. `navigator.share` — the native sheet, which is the good path on a phone — is reachable from a card and not from the wish's own page, so a reader who was *sent* a wish and wants to pass it on falls back to copying the address bar. That is accepted, not overlooked. It is the one thing to revisit if the page ever gets a CTA to sit beside.

**The trailing `#` goes away by construction.** `shareProfile()` shared the literal path `'#'`, which `new URL('#', window.location.href)` resolves to `https://…/u/soheil#` — every profile link shared to date has carried a stray empty fragment. The fix is not a patch to that call: the surviving caller passes a real path, so there is no `'#'` left to resolve.

**The composable keeps its shape and loses its wrapper.** The private `share(path)` — URL resolution, `navigator.share` with a clipboard fallback, three toasts, `AbortError` swallowed so a cancelled share sheet says nothing — is correct as written and stays exactly as it is. It is promoted to the module's export as **`shareLink(path: string)`**; `shareProfile()` is deleted. The rename is because `share()` alone reads as ambiguous with `navigator.share` at a call site.

Wayfinder stays at the call site, not inside the composable: every one of the app's `@/routes` imports lives in a page or a component and none in `composables/` or `lib/`, so `WishCard.vue` imports `show` from `@/routes/wishes` — the same import [The server shape for the wish page](05-the-server-shape.md) already gives it for the cover and title links — and calls `shareLink(show(wish.id).url)`. The composable stays route-agnostic and the card owns knowing what it points at.

**The card's diff, in full:** `import { shareProfile }` → `import { shareLink }`; `@click.prevent="shareProfile()"` → `@click.prevent="shareLink(show(wish.id).url)"`; `aria-label="اشتراک لینک پروفایل"` → `aria-label="اشتراک لینک آرزو"`. The visible «اشتراک» label and `data-test="share"` both stay, and the `wish` prop already carries `id`, so its `Pick` is unchanged.

Nothing tests sharing today — `data-test="share"` has no assertion behind it anywhere in `tests/`. The behaviour worth pinning is that the card's control offers the wish's own URL rather than the current page's.
