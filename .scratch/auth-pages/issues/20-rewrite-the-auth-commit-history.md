# 20 — Rewrite the auth commit history

**What to build:** A history that reads like the auth effort was built stock from the start. Right now it tells a misleading story: commits add a custom register response, custom throttle responses, and custom limiters, and later commits delete every one of them. Nobody reading the log should have to follow work that was undone.

**Why this is safe:** the whole auth effort is unpublished. The remote's default branch still points at the last pre-auth commit, and the local branch is fourteen commits ahead of it with nothing behind. No pushed commit is touched, so no one else's history is rewritten.

**Target shape:** one commit per auth surface — login, register, password recovery, email verification — plus the supporting groundwork (Fortify install, Persian localization, the shared layout and form primitives, the browser-test seam) as its own coherent commits ahead of them. Each commit should build and pass its checks on its own terms, and none should introduce code that a later commit removes.

**Mechanism:** interactive rebase is unavailable in this environment, so drive it with a soft reset back to the pre-auth commit and restage the final tree in logical groups. Tag or branch the current tip first so the original history is recoverable until the result is confirmed good.

Do this last, on a final tree. Any refactor landing after the rewrite reintroduces exactly the churn this ticket exists to remove.

**Blocked by:** 17 — Return throttling to Fortify; 18 — Replace the custom response classes with config; 19 — Disable the passkeys feature.

**Status:** ready-for-agent

- [ ] A backup ref of the pre-rewrite tip exists before any history is rewritten
- [ ] No commit in the rewritten range adds code that a later commit deletes
- [ ] History is grouped one commit per auth surface, with groundwork commits ahead of them
- [ ] The working tree after the rewrite is byte-identical to the tree before it
- [ ] Commit messages follow the repo's short single-subject-line convention
- [ ] Nothing already on the remote is rewritten, and the branch still sits cleanly ahead of it
- [ ] The full suite passes on the rewritten tip, with Pint / Larastan / ESLint / Prettier / vue-tsc clean
