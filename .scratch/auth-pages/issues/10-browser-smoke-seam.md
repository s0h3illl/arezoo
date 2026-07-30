# 10 — Browser smoke seam, proven on Login

**What to build:** The repo's first browser tests: wire up Pest 4 browser testing and prove the seam with one smoke test that visits the login page and asserts no JavaScript errors. Kept deliberately to smoke level — this ticket exists so the tooling risk is paid down on a page that already works, and so tickets 11–14 can each add their page's smoke test as one more file.

**Blocked by:** 08 — Land the Login layout refactor.

**Status:** ready-for-agent

- [ ] Pest 4 browser testing runs locally via the standard test command
- [ ] A smoke test visits the login page and asserts no JavaScript errors
- [ ] The whole suite (feature + browser) passes in one run
