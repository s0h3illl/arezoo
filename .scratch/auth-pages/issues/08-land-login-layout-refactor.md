# 08 — Land the Login layout refactor

**What to build:** Finish and commit the in-flight refactor already sitting uncommitted in the working tree: the login page renders inside the shared RTL app layout (slim header component) instead of carrying its own header and page chrome. This is the card pattern every other auth page copies, so it lands first. Prefactor — no new behavior.

**Blocked by:** None — can start immediately.

**Status:** done

- [x] Login renders inside the shared app layout with the slim header; visual result matches the login design spec
- [x] The existing authentication feature tests pass unchanged
- [x] The working tree's in-flight changes (layout, header component, login page) are committed
