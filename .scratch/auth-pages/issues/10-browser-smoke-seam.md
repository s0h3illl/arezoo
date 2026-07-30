# 10 — Browser smoke seam, proven on Login

**What to build:** The repo's first browser tests: wire up Pest 4 browser testing and prove the seam with one smoke test that visits the login page and asserts no JavaScript errors. Kept deliberately to smoke level — this ticket exists so the tooling risk is paid down on a page that already works, and so tickets 11–14 can each add their page's smoke test as one more file.

**Blocked by:** 08 — Land the Login layout refactor.

**Status:** done

- [x] Pest 4 browser testing runs locally via the standard test command
- [x] A smoke test visits the login page and asserts no JavaScript errors
- [x] The whole suite (feature + browser) passes in one run

## Comments

**Playwright is pinned to an exact version (`1.61.1`), deliberately.** The Playwright CDN (`cdn.playwright.dev`) answers `403 — this service is not available in your location` from here, so `playwright install` cannot fetch a browser build. Playwright 1.61.x pins Chromium revision `1228`, which is already present in the local browser cache, so pinning that version is what makes the seam runnable. Each Playwright minor bumps the required Chromium revision, so a floating range would silently break the suite the next time the lockfile is refreshed. Anyone adding a page smoke test (tickets 11–14) should leave the pin alone unless the download path becomes reachable.

**Shape for tickets 11–14:** one file per page under `tests/Browser/Auth/`, named `<Page>SmokeTest.php`. `tests/Pest.php` binds `TestCase` + `RefreshDatabase` to `Browser` the same way it does for `Feature`, so browser tests get factories, `actingAs()`, and a clean database. Use `route('name', absolute: false)` — `visit()` needs the path, not the `APP_URL` host, because the plugin serves the app on its own port.

**Note:** the smoke test asserts on Persian copy that only exists once Vue mounts, so a broken bundle fails the test rather than passing vacuously.
