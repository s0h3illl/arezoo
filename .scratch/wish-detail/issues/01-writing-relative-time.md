# 01 — How this app writes «۲ روز پیش»

Type: research
Status: resolved
Blocked by: none

## Question

The design dates every contributor row relatively — «۲ روز پیش», and «همین الان» for a row that has just landed — and calls it "a server-rendered relative string". Nothing in the app writes relative time today: `resources/js/lib/format.ts` has `formatMoment`, which is an absolute Jalali date and time from `Intl.DateTimeFormat('fa-IR')`, and its header comment argues explicitly against taking a date library as a dependency "for two lines".

So: what can this app already reach for, without adding a dependency?

Find out, and report facts rather than a recommendation:

- Does `Intl.RelativeTimeFormat('fa-IR')` produce idiomatic Persian in the browsers this app targets, and what exactly does it emit for the ranges a contributor list hits — minutes, hours, days, months? Does it give anything for "just now", or must a caller special-case it?
- Does Carbon's `diffForHumans()` have a Persian localisation available in the installed `nesbot/carbon`, and does it need a locale set that this app does not set anywhere? Check what `config/app.php` and `AppServiceProvider` do about locale today.
- If the string is written server-side, what happens to it — a relative string computed at render time goes stale on a page left open, and Inertia will not re-render it. Note whether anything in the app already has this problem.
- Whichever side writes it, the machine-readable original still has to travel: `format.ts` says callers "put the machine-readable original on a `<time>` element beside whatever these return". Note what shape that implies for the prop.

Capture the findings as a Markdown file in the repo on a throwaway `research/relative-time` branch, and leave a pointer here. (That branch was merged into `main` and deleted on 2026-09-02; the findings file is committed.)

This blocks [What a contributor row shows](03-what-a-contributor-row-shows.md), which cannot decide what a row's date is until it knows what is cheaply available.

## Answer

Findings: [research/01-relative-time.md](../research/01-relative-time.md), on the
`research/relative-time` branch, since merged to `main` and deleted. Measured against the installed versions, not read
off documentation.

Both routes are already installed and neither needs a dependency.

**Carbon `diffForHumans()` — the direction taken.** `nesbot/carbon` 3.13.1 ships
`Lang/fa.php`, and the locale is *already* `fa` at boot with nothing of ours setting
it: `config/app.php:81` defaults `app.locale` to `fa`, and Carbon's own
auto-discovered `Carbon\Laravel\ServiceProvider` pushes that into `Carbon::setLocale`.
`AppServiceProvider` needs no change. Bare `diffForHumans()` — no argument, or it
switches to «پیش از» comparison syntax — gives «۲ روز پیش» wording and picks the unit
itself, collapsing days into weeks at 7 (10 days reads «۱ هفته پیش», never «۱۰ روز پیش»).

**Its one gap: Latin digits.** Carbon returns `2 روز پیش`. Every formatter in
`format.ts` returns Persian digits, and the design asks for «۲ روز پیش». The `intl`
extension is loaded, so one `preg_replace_callback` through
`new NumberFormatter('fa', NumberFormatter::DECIMAL)` yields `۲ روز پیش` — the same
intl data the front end already uses. Ticket 03 owns where that helper lives.

**«همین الان» is app copy either way.** Carbon's `JUST_NOW` option reaches
`fa.php`'s `diff_now` => «اکنون», and only at a zero-second delta — 3 seconds still
reads «۳ ثانیه پیش». `Intl`'s `numeric: 'auto'` gives «اکنون» on the same terms.
Neither library says «همین الان»; the word and the threshold are this app's to choose.

**`Intl.RelativeTimeFormat('fa-IR')`, for the record.** Idiomatic and natively in
Persian digits; `numeric: 'always'` matches the design exactly, while `numeric: 'auto'`
contradicts it (1 day → «دیروز», 2 days → «پریروز», 1 week → «هفتهٔ گذشته»). `style` is
inert for `fa`. Browser support is not a constraint — Vite 8's default target floor is
Safari 16.4, well past it. It loses on one point: it formats a value and unit handed to
it and derives neither from a delta, so the thresholds would be app code that Carbon
already owns.

**Staleness is real and unprecedented here.** A server-written string is fixed at
render time and Inertia will not recompute it, so a page left open keeps saying
«۲ دقیقه پیش». Nothing in `resources/js/` polls or ticks, and nothing in the app calls
`diffForHumans` today — every date it shows is absolute, so no existing screen has this
problem. Also note `config/inertia.php:19` enables SSR, so a client-side computed would
prerender against the SSR clock and recompute on hydration.

**Prop shape: the relative string is a second field, not a replacement.** Precedent is
that resources ship the raw Carbon (`WishResource:72`, `Admin\UserResource:50`, and
others), serialised as `"2026-08-17T18:12:06.000000Z"`, typed `string | null` in
`resources/js/types/`. `admin/users/Show.vue:22-42` pairs them explicitly —
`<time :datetime="registration.iso">{{ registration.label }}</time>`. The `<time
datetime>` attribute still needs the machine-readable original, so a contributor row
carries the ISO timestamp *and* the human string.
