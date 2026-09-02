# 14 — An avatar can be set

**What to build:** A user picks a picture on the account screen and it becomes the avatar their public profile already renders — closing a gap where the app displays an avatar it gives nobody any way to set. The file is resized in the browser before it is sent, so a phone photo does not arrive at full size and does not arrive carrying its location.

**Blocked by:** 13.

**Status:** ready-for-agent

Spec: [The avatar is downscaled in the browser, not on the server](../spec.md). Decision: [How an avatar is carried](01-how-an-avatar-is-carried.md).

- [ ] The browser takes the largest centred square, scales it to 512×512, and re-encodes to WebP before posting. The server cannot resize — the `gd` extension is confirmed absent — so this is the only place it can happen.
- [ ] Re-encoding strips EXIF as a side effect. That is the whole of the fix for a location leak on a public page that also carries the person's real name, and it should be stated at the call site so it is not later optimised away.
- [ ] No fallback branch for WebP: a browser that cannot encode it hands back a PNG, which is larger but valid and still accepted.
- [ ] The centre crop is not a loss of control — both render sites already centre-crop inside a circle, so the stored file simply matches what was always displayed. No drag-and-zoom cropper.
- [ ] The server validates bytes only: nullable, an image, and a size ceiling behind a named constant mirroring the wish-thumbnail one. **No dimensions rule** — nothing server-side ever decodes the image, and pinning the server to the canvas's size would silently reject valid uploads the day the canvas changes.
- [ ] The avatar posts with the rest of the profile form, as a spoofed `PUT` with form data. Removal is a boolean flag, exactly as the wish thumbnail's removal already is.
- [ ] **A model accessor converts the stored path to a URL, and both user-facing resources switch to it in this ticket.** The column has always been published raw and bound straight to an image source; nothing caught it because the column is null everywhere and no avatar has ever rendered. The first stored path breaks every avatar unless this lands with it.
- [ ] The wire format and every frontend type are unchanged by that switch.
- [ ] The store/replace/delete shape is lifted from the existing wish-thumbnail change, including deleting the previous file on replace.
- [ ] Feature tests fake the disk and assert the stored file exists, is replaced, and is deleted on removal. The happy path posts a **real fixture** — the fake-image helper throws without `gd`. Refusals use the byte-writing fake, which does not need it.
- [ ] **Known gap, recorded not worked around:** the canvas downscale has no automated coverage in either seam. The browser test runner cannot POST multipart, so a forced form-data body arrives empty and reads as a broken binding. Do not invent a harness for it.
