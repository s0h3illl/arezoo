# The owner reads every message, whatever the Visibility

The wish's owner reads every message left on a paid contribution to their wish, at all three visibilities. Visibility governs whose **name** sits above the message — `hidden` prints «ناشناس» and no avatar, exactly as a contributor row masks one — and never whether the message is read at all.

This looks like it should be symmetric — a setting that hides the person ought to hide what the person wrote — and it is not, for a reason worth recording, and with a weakness worth stating plainly rather than glossing.

The weakness is real. A message is free text, so masking the name does not mask the body: «مامان سلام، ساراام» hands the owner the identity the setting promised to withhold, and no rule the app can enforce stops a contributor from signing their own note. Under this decision **`hidden` means "my name is not printed", not "the owner learns nothing"** — a label-level mask, not a guarantee.

The trade is accepted anyway because of what the alternative costs. Withholding the message does not withhold it politely: the contributor writes a note, pays, and the note is silently discarded, with nothing in the app telling them so. They believe they were heard and they were not. Between a weakened promise and a discarded message, the weakened promise is the one both parties can see — the owner sees a masked row and knows a name was withheld, the contributor sees their message delivered and can choose what to put in it. The discarded version is invisible from both ends, which is the worse failure of the two.

This reverses the earlier rule, which said a message stayed unread under `hidden` because a message can name the person the setting exists to conceal. That reasoning was right about the risk and wrong about the remedy: it treated a free-text field as if it could be made safe by a visibility column, and paid for the appearance of safety with the contributor's words.

## Considered Options

- Keep the message unread under `hidden`, showing only `public` and `owner` rows in the inbox (the original rule, and charting's filter) — rejected once the cost was named: it destroys what a contributor wrote without telling them, and it buys a guarantee it cannot actually make, since the same person could have written the same identifying sentence under `owner` where the rule always let it through.
- Show the message but warn the contributor at the point of writing that the owner will read it whatever their Visibility — not rejected on the merits, and out of scope here: this decision is about what the inbox shows. The contribution form is another effort's, and if it ever says this, this ADR is what it would be saying.
- Strip or scan messages on masked rows for names — rejected outright. There is no rule that separates a signature from a sentence, and a filter that half-works is a worse promise than a stated one.
