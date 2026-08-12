# Spec: User profile

Status: ready-for-agent

## Problem Statement

The app has wishes, contributions, payments, and a panel for an admin to watch all three — and no screen where the people it serves can see any of it. A user signs up, verifies their email, and arrives nowhere. `AppHeader` renders their account button as a literal `href="#"` with a comment admitting the profile section isn't built. Nothing in the app can create a wish: the `wishes` table is populated only by its factory, so every wish on the platform was put there by a seeder.

The landing page has already sold the thing that doesn't exist. It promises a list built in seconds, a link that works everywhere, and friends who can open it without an account. Three of its four feature cards and all three of its numbered steps describe a page nobody can reach. The primary call to action on the busiest screen in the app leads to a registration form whose reward is a dead end.

There is also no way to name a user in a URL. The `users` table has an id, a name, and an email; a link a person is meant to send to their friends can be built out of none of those.

## Solution

A public profile at `/u/{username}`: whose page it is, and every wish they have published, as a grid of cards showing how close each one is to being paid for. Anyone may read it — signed in or not, that is the point of it — and the owner gets two things nobody else does: a button that opens a modal to add a wish, and a delete control on each card.

To make that URL possible, a user picks a username when they register. To make the page worth sharing, each card carries a share button that hands the profile link to the operating system's share sheet, or to the clipboard where there is no share sheet.

Deleting is where this feature touches money. An owner may delete any wish at any time, including one people have already paid towards — and the contributions must survive it, because balances and withdrawals are derived from exactly those rows. So wishes become soft-deleted, which is what ADR-0003 already decided and what nothing has yet implemented.

## User Stories

1. As a user, I want a profile page of my own, so that signing up leads somewhere rather than nowhere.
2. As a user, I want my profile at a URL built from a name I chose, so that the link I send to friends looks like it belongs to me.
3. As a user, I want to pick my username while registering, so that my page has its address from the moment my account exists.
4. As a user, I want to be told immediately if a username is taken, so that I am not rejected after filling in the rest of the form.
5. As a user, I want my username to be changeable in principle, so that a name I regret is not a life sentence — even though the screen to change it comes later.
6. As a user, I want my name shown on my profile, so that a visitor knows whose wishes these are.
7. As a user, I want a picture and a short bio to have a place on my profile, so that the page is recognisably mine once I can set them.
8. As a user, I want a sensible placeholder where my picture would be, so that a profile without one looks unfinished rather than broken.
9. As a user, I want to see how many wishes I have published, so that the page tells me its own size at a glance.
10. As a user, I want to add a wish without leaving my profile, so that adding several in a row is one continuous act.
11. As a user, I want the title and the price to be the only things I must supply, so that adding a wish is genuinely quick.
12. As a user, I want to add a description, a product link, and a picture when I have them, so that a wish can be as complete as I want it to be.
13. As a user, I want an empty title or price to stop the submission and tell me why, so that I never create a wish that cannot render.
14. As a user, I want the modal to stay open and keep what I typed when something is rejected, so that a mistake in one field does not cost me the other four.
15. As a user, I want the new wish to be on the page when the modal closes, so that I can see that it worked.
16. As a user, I want to close the modal with Escape, the close button, or a click outside it, so that it never traps me.
17. As a user, I want to delete any of my wishes, whatever state they are in, so that my page is mine to curate.
18. As a user, I want to confirm before a wish is deleted, so that one misplaced click cannot remove something.
19. As an owner, I want deleting a wish to leave every contribution to it intact, so that money people gave me is still money I can withdraw.
20. As a user, I want each card to show how much has been raised against its price, so that the page answers the only question anyone opens it to ask.
21. As a user, I want a wish that has been over-funded to say so honestly, so that a bar sitting at full does not hide that people gave more than I asked for.
22. As a user, I want a share button on each card, so that sharing is available wherever my eye already is.
23. As a user, I want sharing to use my phone's own share sheet, so that I can send the link through whatever app I already use.
24. As a user, I want sharing on a desktop to copy the link instead, so that the button is never dead.
25. As a user, I want to be told the share worked, so that a silent button does not leave me wondering.
26. As a user, I want my grid to load more wishes as I scroll, so that a long list neither truncates nor arrives all at once.
27. As a user with no wishes yet, I want the page to tell me what to do, so that an empty profile is an invitation rather than a fault.
28. As a visitor, I want to read someone's profile without an account, so that the link they sent me simply works.
29. As a visitor, I want no delete controls and no add button on someone else's profile, so that I cannot act on wishes that are not mine.
30. As a visitor, I want a profile with no wishes to say so plainly, so that I do not think the page failed to load.
31. As a visitor, I want a profile that does not exist to answer as a missing page, so that a mistyped link fails clearly.
32. As an admin, I want my own profile to work exactly like anyone else's, so that being staff does not cost me the use of the app.
33. As an owner of the system, I want a blocked user's profile to be unreachable, so that blocking is a real removal from the app and not a cosmetic one.
34. As an owner of the system, I want the grid to cost the same number of queries whatever it holds, so that a popular profile does not degrade the site.
35. As an owner of the system, I want the panel's existing user routes to keep resolving by id, so that adding a public URL shape does not quietly widen the admin section.

## Implementation Decisions

### Identity

- **`username` is a real column on `users`**, unique, chosen by the user during registration as a fourth field. It is validated as a dash/underscore-safe slug with a length range, matched case-insensitively for uniqueness, and refused for a small reserved list so that a username can never shadow a routable path.
- It is **changeable in principle** — nothing marks it immutable — but **this work builds no screen to change it.** Renaming, along with name, email, avatar and bio, belongs to an account-settings screen that does not exist. Accepted consequence: taking a username frees the previous one, so a link shared under an old handle can later resolve to a different person. That is a known cost of allowing renames at all, and is worth revisiting when the settings screen is built.
- `avatar` and `bio` are added to `users` as nullable columns **now**, and rendered when present. Nobody can set either until the settings screen lands. They are added early so the profile's shape is final and the later screen only has to wire up inputs.
- **Columns are edited into the existing `create_users_table` migration rather than added as a new one**, and likewise for `wishes`. There is no production data; `migrate:fresh --seed` is the reset path and every seeder already exists.
- `UserFactory` generates a unique username. `DatabaseSeeder`'s admin account gets one like everybody else.

### Routing and access

- The profile is `GET /u/{username}`, resolved by a **second, separate route binding** keyed on the username column. The panel's existing global `user` binding — keyed by primary key, applying `excludingAdmins()` — is left exactly as it is, so no admin route starts accepting a username.
- **The username binding does not exclude admins.** For reading and publishing wishes there is no difference between an admin and anyone else; ADR-0008 is a rule about the admin panel, not about the public site. `CONTEXT.md`'s Admin entry is amended to say so, so the two rules are not read as contradicting each other.
- **A blocked user's profile 404s**, per `CONTEXT.md`: nothing of theirs is reachable by anyone else. The binding filters them out, so the rule is enforced once rather than in the controller.
- A username that matches nobody 404s through the app's existing Persian error page.
- **Guests may read any profile.** No `auth` middleware. This is the promise the landing page already makes.
- `AppHeader`'s account button stops being `href="#"` and points at the signed-in user's own profile. Its placeholder comment is removed.

### The page

- Header: avatar · name · `@username` · bio when set · a single «۳۰ آرزو» chip · «آرزوی جدید» for the owner. The chip counts what the grid holds, so the two can never disagree.
- **The wish count chip is the only stat.** The list chip dies with lists. A total-raised chip is deliberately not added: it would publish a person's incoming money to anyone holding the link, and reads as a Balance, which it is not.
- **No privacy on wishes.** The spec's privacy chip and its hidden-from-visitors rule were both properties of a list. A profile is a page built to be sent to people; everything on it is public. No column, no chip, no filtered query — and no second visibility vocabulary to collide with a Contribution's `visibility`.
- **Ordering** is newest first, with the id as a tiebreak, matching the convention the admin dashboard's wishes table already set for rows that can share a timestamp.
- **The grid is an infinite scroll**, served with `Inertia::scroll()` over a paginator and rendered with the `<InfiniteScroll>` component. Merging only applies to partial reloads, so the redirect after a store or a delete resets the grid to the first page — acceptable, since newest-first puts a new wish on that page anyway.
- **There is no in-grid add tile.** The dashed create-list tile dies with lists and is not repurposed: the header button is the only way to add a wish, in one predictable place regardless of scroll position.
- **Empty states** are two different things. An owner with no wishes gets copy plus a button that opens the same modal. A visitor sees copy alone.

### The wish card

- Cover (`thumbnail`, striped placeholder when null) · title · description when present · a progress bar with the raised and target figures · delete (owner only) · share.
- **The raised figure is one aggregate query for the whole grid**, constrained to paid contributions — the same definition `Wish::receivedTotal()` already uses. Not a query per card.
- **The bar clamps at 100%, the numbers do not.** Contributions are uncapped (ADR-0004), so an over-funded wish shows a full bar above «۷۰۰,۰۰۰ از ۵۰۰,۰۰۰» and «۱۴۰٪». The bar is a gauge; the figures are the record. Passing `min(received, price)` as the value with `price` as the max produces the clamp and a `data-state="complete"` hook in one go.
- **The card is inert and carries no purchase link.** `purchase_link` belongs on the wish detail page, where somebody deciding to contribute needs it; that page is planned and out of scope here. Nothing on the card is a link, which also keeps the delete and share buttons out of a larger click target.
- Money is Toman (ADR-0002) through the existing `formatToman`; digits are fa-IR throughout.

### Adding a wish

- A reka-ui `Dialog`, which supplies the focus trap, Escape handling, backdrop dismissal, `aria-modal`, and body scroll lock the spec asks for, rather than hand-rolling them.
- Fields: **عنوان (required)**, توضیح, لینک محصول, **قیمت (required)**, تصویر.
- **`price` is required even though the design called it optional.** The card's progress bar has no meaning without a target, so a priceless wish would need a second card state that nothing else in the app wants.
- **`description` and `purchase_link` become nullable columns**, matching the design's intent that both are optional. The card omits the description row when it is absent.
- **The thumbnail is an optional upload**, validated as an image with a size cap and stored on the public disk exactly as `WishFactory` already does. **`php artisan storage:link` is required** — the symlink has never been created, and no screen has ever rendered a thumbnail before this one.
- Submitted with `useForm().post()` and `forceFormData`. **No optimistic update**: the server's response is the truth, so the grid cannot disagree with the database, and there is no provisional card to invent an id and a local cover image for. A 422 leaves the modal open with errors inline; success closes it, resets it, and the new wish is simply in the refreshed props.

### Deleting a wish

- **An owner may delete any of their wishes, in any condition**, including wishes that have received money. There is no rule about contributions blocking a delete.
- **`Wish` gains `SoftDeletes` and a `deleted_at` column.** A soft delete never fires the foreign key, so contributions survive untouched — which is required, not merely tidy: balances and withdrawals are derived from exactly those rows (ADR-0005), and destroying them would silently shrink money an owner has already earned.
- This implements the wish half of ADR-0003, which was decided but never built. `.scratch/contributions/issues/05` shrinks to the user half.
- **The FK stays `cascadeOnDelete`**, which is now reachable only through an explicit `forceDelete()`. Nothing in the app calls it; it remains the deliberate full-purge path ADR-0003 describes.
- Deleting is owner-only, authorised server-side rather than merely hidden in the UI, and confirmed before it fires.

### Sharing

- A **`useShare.ts` composable** wrapping `navigator.share`, falling back to `navigator.clipboard.writeText` where the share API is absent — which includes desktop Linux and Firefox, so the fallback is the path that runs during development.
- **A toast fires on both paths**: «لینک به اشتراک گذاشته شد» after a native share, «لینک کپی شد» after a clipboard copy. An `AbortError` from a dismissed share sheet is not an error and toasts nothing.
- Toasts are reka-ui `Toast*` primitives. reka-ui has no global `toast()` function, so a small imperative wrapper over a reactive list is written once, with the `ToastViewport` mounted in `AppLayout` so any page can raise one.
- **Every card shares the profile URL**, `/u/{username}`. There is no wish URL yet. Accepted consequence: every share button on the page sends the same link. When the wish detail page lands, the target changes in one composable.

### Components

- reka-ui supplies `Avatar` (image with fallback), `Progress` (root and indicator), `Dialog`, and `Toast`. It is already installed at 2.10.3, currently uncommitted alongside `bun.lock`.
- Wayfinder generates the route helpers; no URL is hardcoded in the frontend.
- The wish shape the profile reads is served through a dedicated resource, following the convention the admin screens set. The existing `resources/js/types/wish.ts` describes the admin's narrower shape and is not widened to serve both — per the repo's rule that `types/` holds original types, with page-specific derivations local to the page.

## Testing Decisions

- **One seam: Pest feature tests over the HTTP routes, asserting Inertia props and status codes**, exactly as the admin panel's tests already do. No unit seam for the aggregate or the binding — the props are the behaviour.
- `ProfileTest` covers: a profile renders for its owner, for a signed-in visitor, and for a guest; the owner-only flags differ between them; an unknown username 404s; a blocked user's profile 404s; an admin's profile renders normally; the wish count matches the grid; ordering is newest-first with a predictable tiebreak; the raised figure counts paid contributions and ignores pending ones; the grid does not run a query per card.
- `WishTest` covers: an owner stores a wish with only a title and a price; the optional fields persist when given; a missing title or price is rejected; a thumbnail upload lands on the disk (`Storage::fake`); a visitor and a guest are both refused the store route; an owner deletes their own wish; a visitor and a guest are refused the delete; **a deleted wish's contributions still exist afterwards, and the owner's derived total is unchanged** — the assertion that justifies the soft delete.
- **Browser tests stay at smoke level**, matching every existing one: `ProfileSmokeTest` loads a profile and asserts the Persian copy renders without JavaScript errors. `useShare`'s branches, the toast, and the dialog's keyboard handling are deliberately not covered — stubbing `navigator.share` would test the stub more than the code.
- Browser test selectors must be explicit; a bare tag name is not matched as a tag.
- Existing tests that change: `AppHeaderTest`, whose account button assertion currently expects `#`; any registration test asserting the field set; and `UserFactory` consumers, if any assert an exact attribute list.
- `php artisan migrate:fresh --seed` completes, Pint clean, larastan clean, `bun run types:check` clean.

## Out of Scope

- **The account-settings screen** — changing name, username, email, password, avatar, or bio. The columns exist; the screen does not.
- **The wish detail page**, and with it `purchase_link` appearing anywhere in the UI. Explicitly planned as the next piece of work.
- **Contributing to a wish.** The profile shows how much has been raised and offers no way to add to it.
- **Editing a wish.** Wishes can be created and deleted, not changed.
- Restoring a soft-deleted wish, and any UI that reveals one exists.
- User soft-deletes — still `.scratch/contributions/issues/05`.
- Per-wish or per-profile privacy, and any notion of a wish being fulfilled or closed.
- Following, discovering, or searching for users. There is no directory; a profile is reached by knowing its URL.
- A total-raised figure on the profile header, and any per-wish contributor count.
- Reserving a username for a user who registered before the column existed — there are none.
- Any change to the admin panel.

## Further Notes

- Domain vocabulary per `CONTEXT.md`, which this work amends with **Profile**, **Username**, and **Visitor**. A Visitor is anyone reading a profile that is not theirs, signed in or not; a **Guest** remains specifically someone signed out. Copy and test names should not drift — not "handle" or "slug" for a username, and not "guest" where "visitor" is meant.
- **The spec this was derived from is list-centric and the app has no lists.** `profile-spec.dc.html` §2 (wishlist grid and card), §2.2 (create-list tile), §3's list picker, §1.2's list chip, §4's per-list data model, and §6's `showPrivacy` / `showCreateTile` flags all describe a concept that does not exist. They are not deferred; they are removed. §6's remaining flags are design-tool switches, not application config: `isOwner` is derived server-side from the viewer.
- The design's `/l/:id` share URL and its `/u/:username` hero link both assumed routes that do not exist. Only the second survives, and this work creates it.
- ADR-0003 was written and never implemented. This feature implements half of it because a delete button cannot be built correctly without it. That is worth reviewing as an ADR finally landing, not as an incidental schema change.
- No new ADR is proposed. The soft-delete decision is already ADR-0003; the admin-is-an-ordinary-user clarification is a glossary amendment rather than a trade-off with genuine alternatives.
- reka-ui arrives in this branch as an uncommitted dependency change. It should be committed deliberately, with the components that justify it.
