# 04 — What the inbox shows, and how much of it loads

Type: grilling
Status: resolved
Blocked by: none

## Question

[Charting](00-charting.md) fixed the row set exactly: contributions to the reader's own wishes where `status = paid`, `message is not null`, and `visibility in (public, owner)`. A `hidden` contribution's message never appears at all — absent, not anonymised — so **every row here carries a real name**, which is the opposite of a contributor row on a wish page.

What a row *shows*, and how many arrive, is open.

Decide:

- **The fields.** The message is the point. Around it: the contributor's name and avatar, the wish it was written for, the amount, and the date. Say which are in. Note that `wish-detail`'s contributor row settled on `User::$name` as plain text with no link to `/u/{username}`, and `Profile.vue`'s `AvatarRoot`/`AvatarImage`/`AvatarFallback` pattern for the picture — diverge only with a reason.
- **A long message.** `contributions.message` is a `text` column with no length rule anywhere, because nothing has ever written one. Decide the cap at write time (which belongs to the contribute effort, so record it as a constraint) and what this page does with what it is given — clamp with a "more", wrap freely, or scroll.
- **Ordering.** `settled_at` descending, almost certainly — `created_at` records when the gateway attempt began, and ADR-0005's reasoning is that the two are deliberately different. Confirm, and decide the tiebreak.
- **How many load.** `wish-detail` settled on `Inertia::scroll()` + `<InfiniteScroll>` at 7 per page with no visible "more" affordance, matching the profile grid. An inbox is read top-down and probably wants the same, but 7 is sized for a card grid rather than a list — decide the page size on this list's own terms.
- **The wish, when the wish is gone.** ADR-0003 and soft deletes mean a message outlives its wish. Decide what the row reads as and whether the wish name still links — `wishes.show` 404s a soft-deleted wish, so a live link would be a link to nothing.
- **A deleted contributor.** Users are not soft-deletable yet — `.scratch/contributions/issues/05` owns that, and `Contribution::contributor()` carries no `withTrashed()`. `wish-detail` decided «کاربر حذف‌شده» for this case and shipped without it. Decide the same way: settle the copy, note it is blocked, and do not let it hold the page.
- **The empty state.** Two different empties, and they should not read the same: nobody has contributed at all, versus people have contributed but nobody wrote anything. The second is the one this page invents, since the balance figures will show money against an empty inbox.
- **Whether a wish's own page gains anything.** An owner reading `/wishes/{wish}` sees contributor rows with no messages, by that spec's explicit decision. Decide whether it now links to this inbox, or stays as specced.

## Answer

### The row set — charting's visibility filter is reversed

The inbox carries every **`paid`** contribution to the reader's own wishes whose **`message is not null`** — **at all three visibilities**. `hidden` is no longer filtered out. A `hidden` contribution's message is shown, with its contributor masked exactly as a contributor row masks one: «ناشناس», the «؟» circle, no avatar, no id.

This reverses [charting](00-charting.md) and contradicts `CONTEXT.md:38`, which says a message "is narrower still: only ever the wish's owner reads one, and only while the contributor is visible to them — under `hidden` it stays unread too, since a message can name the person the setting exists to conceal."

The argument against was put and declined. It is real and it is not cosmetic: a message is free text, so masking the name label does not mask the body. «مامان سلام، ساراام» hands the owner the identity the setting promised to withhold, and no amount of server-side masking can read a sentence. Under this decision **`hidden` means "my name is not printed", not "the owner learns nothing"** — a label-level mask, not a guarantee.

The reason it is the better trade anyway: the alternative silently discards what a contributor wrote. Someone pays, writes a note, picks `hidden` because they do not want their name on a public page, and the note is never read by anyone, ever — with nothing in the app telling them so. Between a weakened promise and a silently destroyed message, the weakened promise is the one the owner and the contributor can both see.

**Two obligations follow, neither optional:**

1. **`CONTEXT.md:38` is rewritten.** A message is no longer narrower than identity. The new rule: the wish's owner reads every message on a paid contribution to their wish; Visibility governs whose *name* sits above it, never whether it is read.
2. **This needs an ADR of its own**, in `docs/adr/`, alongside ADR-0010. It is a domain rule with a privacy consequence, not a page detail, and the reasoning above is the ADR's body: the leak the mask does not close, and why the decision stands regardless. ADR-0010's shape is the model — it records the same kind of "this looks like it should be symmetric and is not" finding.

A third thing is worth doing and is **not this map's to do**: the contribute form should tell the contributor plainly that the owner reads their message whatever the visibility. Noted for the contribute effort, not ticketed here.

### Ordering

**`settled_at` desc, `id` desc.** `created_at` was asked for and declined on one fact: it records when the gateway attempt *began*, `settled_at` when `markPaid()` verified it, and `ContributorRow.vue` already *displays* `settled_at`. Sorting by `created_at` would order the list by a column it does not show, so a row could sit above an older-looking date. Every paid row has a `settled_at` — `markPaid()` writes status and timestamp in one transaction — so there are no nulls to sort around. `id` desc breaks a same-second tie.

### How many load

**`Inertia::scroll()` + `<InfiniteScroll>`, 15 per page, no visible "more" affordance.** The mechanism is the app's only list pattern (`ProfileController::WISHES_PER_PAGE`, `WishController::CONTRIBUTIONS_PER_PAGE`, both 7); the number is not. Seven is sized for a card grid and a forty-pixel contributor row; a message row is several lines tall and read top-down, so 15 is what fills a first screen here. Numbered pagination was rejected — the app has no such component anywhere, and inventing one runs against this map's standing preference.

### The fields

Message, contributor name, contributor avatar, wish title, amount, date.

- **The message in full.** No clamp, no «بیشتر», no scroll container — `whitespace-pre-wrap`, wrapping freely, the way `wishes/Show.vue:135` already renders a wish description. The length is defended where it is written, not where it is read.
- **Name and avatar** follow `ContributorRow.vue` exactly: name as plain text with **no link to `/u/{username}`**, `AvatarRoot`/`AvatarImage`/`AvatarFallback` for the picture, the grey «؟» circle when masked. **No contributor `id` reaches the browser**, so a masked row cannot be traced back to a user — the property `ContributionResource` protects today.
- **Amount** is unconditional on every row, masked or not (ADR-0010).
- **Date** is `settled_at`, relative, the same treatment the contributor row gives it.

### The wish, when the wish is gone

**The title is always shown as plain text, and never links** — not for a live wish either. `Contribution::wish()` already carries `withTrashed()` (ADR-0003), so a trashed wish's title reads exactly like a live one and needs **no «حذف‌شده» marker and no special copy**. Nothing about soft deletion reaches the page.

The consequence, accepted: an owner who wants to open a wish they read a message about goes to their own profile to find it. The alternative — a nullable `wish.url` the server computes — buys one hop and makes the page carry a state it otherwise never sees.

### A deleted contributor

**«کاربر حذف‌شده»**, replacing the name only; the message and amount still show. Cannot happen today — users are not soft-deletable (`.scratch/contributions/issues/05` owns that) and `Contribution::contributor()` carries no `withTrashed()`. Settled as copy, recorded as blocked, and explicitly **not** allowed to hold this page, the same call `wish-detail` made. It is a third state, distinct from «ناشناس»: reusing the anonymous copy would tell the owner the contributor chose to hide when in fact the account is gone.

### The empty state

**One line covering both cases**, e.g. «هنوز پیامی نداری». The two-state version — "nobody contributed" versus "people contributed but nobody wrote" — was put and declined: it costs a second count query on every page load to phrase an absence.

The accepted consequence: an owner whose withdrawals page shows real money can find an empty inbox and be told only that there are no messages, with nothing explaining that a message is optional. Note that this decision's own first section shrinks the second case — with `hidden` no longer filtered, a null `message` is now the *only* reason a paid contribution is missing from this list.

### The server shape

**A new `MessageResource`**, not an extension of `ContributionResource`:

```
{ id, state: 'visible'|'anonymous', contributor: { name, avatar }, message, wish: { title }, amount, settled_at }
```

`state` and the `contributor` block reuse `ContributionResource`'s logic verbatim — masked rows carry «ناشناس» and a null avatar as **present keys**, never absent ones. `message` and `wish` are the two keys that are new.

`ContributionResource` is **not touched**. `wish-detail`'s spec states "No `message` key anywhere" and dropped the contributor `id` for the same reason; adding a conditional `message` to that resource would make the wish page's payload depend on a condition not firing, which is precisely the fragility that decision was written to prevent. Two resources, one guarantee that holds structurally.

### Constraint recorded for the contribute effort

**`message` is `max:512` characters, validated when the contributor submits — before the redirect to the gateway.** The column is `text` with no rule anywhere today because nothing has ever written one. This map does not build the contribute form; the cap is recorded here so the inbox's "render it whole, never clamp" decision rests on something.

### The wish page is unchanged

`/wishes/{wish}` does **not** gain a link to the inbox. The header account menu carries پیام‌ها from every page in the app, that one included, so a link here would be the same destination built twice — and `wish-detail` is shipped. Its contributor rows keep showing no messages, exactly as specced.
