# 14 — An avatar can be set

**What to build:** A user picks a picture on the account screen and it becomes the avatar their public profile already renders — closing a gap where the app displays an avatar it gives nobody any way to set.

**Blocked by:** 13.

**Status:** done

Spec: [The avatar is downscaled in the browser, not on the server](../spec.md). Decision: [How an avatar is carried](01-how-an-avatar-is-carried.md).

**Descoped:** the browser-side crop/resize/WebP re-encode described in the spec (largest centred square → 512×512 → WebP, EXIF stripped as a side effect, no dimensions rule server-side because of it) was not built and is not wanted for now. The picked file is posted to the server as-is.

- [x] The server validates bytes only: nullable, an image, and a size ceiling behind a named constant mirroring the wish-thumbnail one.
- [x] The avatar posts with the rest of the profile form, as a spoofed `PUT` with form data. Removal is a boolean flag, exactly as the wish thumbnail's removal already is.
- [x] **A model accessor converts the stored path to a URL, and both user-facing resources switch to it in this ticket.** The column has always been published raw and bound straight to an image source; nothing caught it because the column is null everywhere and no avatar has ever rendered. The first stored path breaks every avatar unless this lands with it.
- [x] The wire format and every frontend type are unchanged by that switch.
- [x] The store/replace/delete shape is lifted from the existing wish-thumbnail change, including deleting the previous file on replace.
- [x] Feature tests fake the disk and assert the stored file exists, is replaced, and is deleted on removal. The happy path posts a **real fixture** — the fake-image helper throws without `gd`. Refusals use the byte-writing fake, which does not need it.
