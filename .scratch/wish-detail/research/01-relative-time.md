# How this app can write «۲ روز پیش»

Findings for [01 — How this app writes «۲ روز پیش»](../issues/01-writing-relative-time.md).
Measured against this repo's installed versions on 2026-08-18, not against documentation.

## Summary of what is available

| | Persian? | Persian digits? | Picks the unit? | «همین الان»? |
|---|---|---|---|---|
| `Intl.RelativeTimeFormat('fa-IR')` | yes | yes, natively | **no — caller must** | «اکنون» at exactly 0s only |
| Carbon `diffForHumans()` | yes | **no — Latin digits** | yes | «اکنون» via `JUST_NOW` |

Both are already installed. Neither needs a new dependency.

## Carbon `diffForHumans()`

`nesbot/carbon` **3.13.1** ships `Lang/fa.php`, `Lang/fa_IR.php` and `Lang/fa_AF.php`.
`localeHasDiffSyntax('fa')` is `true`.

**The locale is already set, and nothing in this app sets it.** `config/app.php:81`
has `'locale' => env('APP_LOCALE', 'fa')`, and `Carbon\Laravel\ServiceProvider` —
auto-discovered from the package — calls `Carbon::setLocale(app()->getLocale())`
in its own `boot()` and re-runs it on the `LocaleUpdated` event
(`vendor/nesbot/carbon/src/Carbon/Laravel/ServiceProvider.php:52-79`). Verified in
a booted app: `Carbon\CarbonImmutable::getLocale()` returns `fa` with no
configuration of ours. `AppServiceProvider` does not mention locale and does not
need to.

Note that Laravel's own `Translator::setLocale`
(`vendor/laravel/framework/src/Illuminate/Translation/Translator.php:576`) does
**not** touch Carbon — the wiring is Carbon's package, not the framework's.

### What it emits

Bare `diffForHumans()`, locale `fa`:

| elapsed | output |
|---|---|
| 30 seconds | `30 ثانیه پیش` |
| 1 minute | `1 دقیقه پیش` |
| 3 hours | `3 ساعت پیش` |
| 1 day | `1 روز پیش` |
| 2 days | `2 روز پیش` |
| 7 days | `1 هفته پیش` |
| 30 days | `4 هفته پیش` |
| 6 months | `6 ماه پیش` |
| 1 year | `1 سال پیش` |

Carbon picks the unit itself and collapses days into weeks at 7 — a contributor
who gave 10 days ago reads «۱ هفته پیش», never «۱۰ روز پیش». `['short' => true]`
changes nothing in `fa`; the abbreviations are identical to the long forms.

**Pass no argument.** `diffForHumans($now)` switches to comparison syntax and
emits `2 روز پیش از` — "before" — which is wrong on its own.

### Digits

`fa.php` carries no numbering system, so Carbon returns **Latin** digits:
`2 روز پیش`. Every other formatter in this app returns Persian digits
(`resources/js/lib/format.ts` — `formatToman`, `formatNumber`, `formatShare`,
`formatMoment` all go through `Intl.*('fa-IR')`, whose numbering system resolves
to `arabext`). The design asks for «۲ روز پیش».

The `intl` extension is loaded, so one pass closes it:

```php
$digits = new NumberFormatter('fa', NumberFormatter::DECIMAL);

preg_replace_callback('/\d+/', fn (array $m): string => $digits->format((int) $m[0]), $string);
```

Measured output: `۲ روز پیش`, `۳۰ ثانیه پیش`, `۱ هفته پیش`, `۶ ماه پیش`. A plain
`strtr` digit map produces the same strings; `NumberFormatter` is the same intl
data the front end already relies on.

### «همین الان»

`fa.php:48` defines `'diff_now' => 'اکنون'`, reached by
`diffForHumans(['options' => CarbonInterface::JUST_NOW])`. It fires only at a
zero-second delta — 3 seconds still reads «۳ ثانیه پیش». Neither Carbon nor Intl
produces the design's «همین الان»; that exact wording is app copy and a
threshold this app chooses (Carbon's own word is «اکنون»).

## `Intl.RelativeTimeFormat('fa-IR')`

Measured under Node 26.5.1, ICU 78.3 / CLDR 48. `supportedLocalesOf(['fa-IR'])`
returns `['fa-IR']`; `resolvedOptions().numberingSystem` is `arabext`.

`numeric: 'always'` (the default) reproduces the design's wording exactly and in
Persian digits:

> `۳۰ ثانیه پیش` · `۲ دقیقه پیش` · `۳ ساعت پیش` · `۲ روز پیش` · `۷ روز پیش` · `۶ ماه پیش` · `۱ سال پیش`

`numeric: 'auto'` substitutes idioms that **contradict** the design: 1 day becomes
«دیروز», 2 days «پریروز», 1 week «هفتهٔ گذشته», 1 month «ماه گذشته». It is the only
mode that says anything for "just now" — «اکنون», and only at exactly 0 seconds.

`style` is inert for `fa`: `long`, `short` and `narrow` are byte-identical across
every range tested.

**The catch:** `RelativeTimeFormat` formats a value and a unit you hand it. It
does not derive either from a delta — no `Intl` API does. Choosing between
seconds, minutes, hours, days and months, and the thresholds between them, would
be app code. Carbon's `diffForHumans` already owns that choice.

Browser support is not a constraint: the app sets no `browserslist` and no
`build.target`, so Vite 8.1.5 applies its `baseline-widely-available` default —
Chrome 111, Edge 111, Firefox 114, Safari 16.4, iOS 16.4
(`node_modules/vite/dist/node/chunks/node.js:610`). `Intl.RelativeTimeFormat`
predates all of those, and Tailwind v4 already puts the floor at Safari 16.4.

## Staleness of a server-written string

Real, and this app has no precedent for it. A string computed in a controller or
resource is fixed at render time; Inertia will not recompute it, so a page left
open overnight keeps saying «۲ دقیقه پیش». Nothing in `resources/js/` polls,
reloads, or ticks — no `setInterval`, no `router.reload` on a timer.

The app has no instance of this problem today because **nothing anywhere calls
`diffForHumans`** (no match in `app/`, `resources/`, or `tests/`), and every date
it shows is absolute. `formatMoment` renders «۲۷ مرداد ۱۴۰۴، ۲۲:۴۲» from the raw
ISO, which cannot go stale.

Note `config/inertia.php:19` has `'ssr' => ['enabled' => true]`. A relative
string computed client-side in a `computed()` would be prerendered against the
SSR process's clock and recomputed on hydration — harmless in output, but a
source of hydration-mismatch warnings. A server-written string has no such split.

## The prop shape

Established precedent: **resources ship the raw timestamp; the page writes the
label.** Every resource passes the Carbon instance straight through —
`WishResource:72`, `Admin\UserResource:50`, `Admin\WishResource:45`,
`Admin\PaymentResource:43` — and Laravel serialises it as
`"2026-08-17T18:12:06.000000Z"` (UTC; `config/app.php:68` sets the app timezone to
UTC). `new Date()` parses that unchanged. Every `created_at` in
`resources/js/types/` is typed `string | null`.

`admin/users/Show.vue:22-42` is the closest precedent and pairs the two
explicitly — a computed returning `{ iso, label }`, rendered as
`<time :datetime="registration.iso">{{ registration.label }}</time>`. The `iso`
half is the prop; the `label` half is built in the page.

So a server-written relative string is a **second** field, not a replacement: the
`<time datetime>` attribute still needs the machine-readable original, which is
what `format.ts`'s header comment means by "callers put the machine-readable
original on a `<time>` element beside whatever these return". Two fields on the
row — the ISO timestamp and the human string — or one ISO field and a label the
page derives.
