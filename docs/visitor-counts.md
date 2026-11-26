# Visitor counts

This site counts page views, file downloads, and a few interactions it chooses, such as a click on the home hero's button. It uses the counts to sort the resources list by what people download lately, shows them on the list's cards, and lists the most visited pages and most downloaded files in the admin. Nothing it stores can identify a visitor.

This guide explains how that works, so you can add the same to another Lynx site. The generic part lives in `app/Stats` and copies over as it is. The rest is this site's own wiring, which you adapt.

## What a visitor and an editor see

- **On the resources page** (`/resources/`): up to four downloads an editor features at the top, then every download, 24 a page, sorted by Trending lately (the default), Most downloaded, Newest, or A to Z. Each card shows its download count, such as "1.2K downloads".
- **In the page editor:** a line in the header with the page's views, and downloads when it has any, over the last 30 days.
- **On the admin home:** views today in the row of numbers at the top, and two lists for the last 30 days: the most visited pages, and the most downloaded files with each file's language.

## How it fits together

```
Browser                          Laravel (app/Stats)                    Database
───────                          ───────────────────                    ────────
<x-stats-beacon> script ──POST /stats/hit──► HitController ──record()──► stat_counts
  · a view after 1 second                     (stateless: no session,     (daily totals)
  · clicks on [data-stats-event]               no cookie)
  · opening <details data-stats-event>

Download form / email ──GET /stats/file/…──► DownloadFileController ───► stat_counts
  (a signed link)                              · counts once per link    stat_links
                                               · redirects to the file  (links counted)

/resources/ template ──► DownloadListing ──► Ranking ──daily()───────────► stat_counts
Admin home, editor   ──► TopLists, PageLine ──top(), totals()──────────► stat_counts
```

Every count is a row of daily totals: one row per page, event, subject, source, and day, whose `count` goes up. A thousand visitors on one page in one day change one row.

## The data

The migration `database/migrations/2026_11_25_000000_create_stat_tables.php` makes two tables.

`stat_counts` holds the totals:

| Column | Holds |
| --- | --- |
| `page_id` | The Lynx page the count belongs to. |
| `event` | `view`, `download`, or a name the site declares (`hero-cta`). |
| `subject` | What within the page, or empty: for downloads, the file's language; for events, a label such as the hero slide's name. Only `[A-Za-z0-9._-]`, up to 64 characters; anything else is stored as empty, so a visitor's text can't end up here. |
| `source` | `site` for what the site counts itself. Another name, such as `cdn`, is for counts brought from elsewhere later. |
| `day` | The day, `Y-m-d`. |
| `count` | The total for that day. |

The primary key is all of the columns except `count`, and an index on `(event, day)` serves the rankings and the admin's lists.

`stat_links` remembers which download links have already been counted (a hash of the link's signature) until they expire. `php artisan stats:prune`, run daily by the scheduler, deletes expired ones.

Both tables use the connection in `config('stats.connection')`, the app's default when `STATS_DB_CONNECTION` is empty. Nothing joins them with Lynx's tables: pages are loaded by id afterwards. So the tables can move to a second SQLite file, or another database, by setting that variable.

## Counting views and events

`resources/views/components/stats-beacon.blade.php` is placed once in the layout, before `</body>`:

```blade
<x-stats-beacon :page="$page" />
```

It prints a small inline script holding the page's id. The id is the same for every visitor, so the page cache can store the page with the script in it, and cached pages still count. The script:

- sends a view with `navigator.sendBeacon` once the page has been visible for a second (not while the browser is prerendering it);
- sends an event when a visitor clicks an element with `data-stats-event`, or opens a `<details>` that has one. `toggle` doesn't bubble, so the script listens on its way down (the capture phase).

To count a new interaction, list its name in `config/stats.php` and mark the element:

```php
// config/stats.php
'events' => ['hero-cta', 'faq-open'],
```

```blade
<a href="/contact/" data-stats-event="hero-cta" data-stats-subject="{{ $slide->name }}">Talk to us</a>

<details data-stats-event="faq-open" data-stats-subject="pricing">
    <summary>How is it priced?</summary>
    …
</details>
```

Names that aren't listed are ignored, so nobody can fill the table with made-up names.

`App\Stats\Http\HitController` receives the beacon at `POST /stats/hit`. It is registered in `StatsServiceProvider` outside the `web` middleware group, so it starts no session and sets no cookie, and needs no CSRF exemption. It records the hit only when every check passes:

1. The request comes from the site itself: `Sec-Fetch-Site: same-origin`, or an `Origin` with the site's host for browsers that don't send it.
2. `RealVisitor::is()` says it's a person's browser (below).
3. The request has no `lynx_bypass` cookie, which Lynx's page cache sets when someone signs in to the admin. That cookie is HttpOnly, so the script can't read it; the server checks it.
4. The event is `view` or a declared name.
5. The page exists, is published, and has an address.
6. The address hasn't sent more than `stats.hits_per_minute` hits this minute. The limit's key is a hash of the address, kept for a minute.

It always answers 204 with an empty body, whatever it decided, so a refusal tells a scraper nothing.

Views are counted as visits: a reload counts again. Counting unique visitors would mean recognizing a visitor across requests, which this design avoids.

## Telling people from bots

`App\Stats\RealVisitor::is($request)` is false for:

- a user agent [crawler-detect](https://github.com/JayBizzle/Crawler-Detect) recognizes: search engines, link previewers, monitors, scrapers;
- an empty user agent;
- a `HEAD` request;
- a prefetch (`Sec-Purpose` or `Purpose` containing `prefetch`).

Most bots don't run JavaScript, so they never send the beacon in the first place; the checks catch the ones that do. The search panel's hit counter (`SearchHitController`) uses the same check.

## Counting downloads

Files are counted when they are fetched, not when the form is sent. A link to a file goes through the site:

```php
use App\Stats\DownloadLinks;

$link = DownloadLinks::for($pagefile, 'document', now()->addDays(7), subject: 'pt');
```

It returns a temporary signed URL to `GET /stats/file/{page}/{field}/{file}` (route `stats.file`). `App\Stats\Http\DownloadFileController`:

1. Refuses a link whose signature is wrong or expired (403), and a page or file that doesn't exist (404).
2. Records one download the first time a real visitor opens the link: the link's signature hash goes into `stat_links`. A second click, and an email provider's link scanner (Outlook Safe Links and similar send browser user agents, so they pass the bot check), don't count again. No address is involved.
3. Always redirects to the file through Lynx's own signed address, valid for 5 minutes (`Pagefile::temporaryUrl`). A PDF viewer's range requests then go to Lynx, never back here. The redirect is never throttled: a visitor always gets the file.

On this site, `App\Downloads\Document::link($language, $expires)` wraps it for the download pages' `document` field, with the language as the subject. The download form (`App\Livewire\DownloadForm`) and the email (`App\Listeners\SendDownloadLink`) both use it.

## Sorting by recent activity

`App\Stats\Ranking::ids($ids, 'download')` orders page ids by how often the event happened lately. Each day's count weighs half as much every `stats.half_life_days` (7):

```
score = Σ count × 0.5 ^ (days ago ÷ 7)
```

| Downloaded | Weighs |
| --- | --- |
| Today | 1 |
| 7 days ago | ½ |
| 14 days ago | ¼ |
| 56 days ago | under 0.4%, and left out from there |

So a page downloaded 5 times yesterday ranks above one downloaded 30 times five weeks ago, while a steady favorite still ranks well. Pages without any activity keep the order they were given, after the others.

The score is computed from the daily totals each time the list's ids are built, never stored. Changing the half-life, correcting a past day, or importing old counts needs no migration and never leaves a stale score behind.

`PageStats` also answers simpler questions:

| Method | Answers |
| --- | --- |
| `record($page, $event, $count = 1, $subject = '', $day = null, $source = 'site')` | Adds to a day's count (today by default). |
| `totals($ids, $event, $days = 0)` | Each page's total over the last `$days` days, or all time with 0. |
| `daily($ids, $event, $days)` | Each page's count per day. |
| `top($event, $days, $limit, $bySubject = false)` | The pages (or the page and subject pairs) with the highest totals. |
| `sum($event, $days = 0)` | The total over every page. |

## The resources list

`App\Downloads\DownloadListing::for($sort, $page)` builds one page of the list:

| Sort | Order |
| --- | --- |
| `popular` (Trending lately, the default) | `Ranking` over downloads, newest first among pages without any. |
| `downloads` (Most downloaded) | All-time download totals, highest first; newest first among equals. |
| `newest` | `published_on`, newest first. |
| `title` | A to Z in the visitor's language. |

Each sort's ids are cached for 15 minutes, the same time the page cache keeps the list, so page 1 and page 2 of a sort come from the same order. An unknown sort or a page past the last returns null, and the template (`resources/views/templates/downloads.blade.php`) answers 404: the page cache then never stores junk addresses such as `?page=9999`.

The template also:

- shows the featured downloads (the Resources page's `featured_downloads` field, up to 4) on the first page only, and nothing when the field is missing. Each one is a `<x-featured-download>` card: its `image` as a thumbnail, or a dark cover with its kind when it has none;
- marks the sort and page links `rel="nofollow"`, so crawlers don't render every combination; the canonical address (printed by lynxcms/seo) is the list without them;
- gets the download counts for every card on the page with one `PageStats::totals()` query, and passes each card its number.

The page cache rule for the list (in `SiteFeaturesSeeder::CACHE_RULES`) keeps a copy per sort and page, for 15 minutes:

```php
'downloads' => ['cache' => true, 'lifetime' => 900, 'parameters' => ['sort', 'page']],
```

Lynx stores at most `lynx-cache.pages.max_variants` copies of a page (50 by default). Four sorts times about 25 pages needs more, so `AppServiceProvider` raises it to 120. It's nested under `pages` in Lynx's config, which is why it's set at boot rather than in a `config/lynx-cache.php` file: such a file would replace the whole `pages` array.

## The admin

`StatsServiceProvider::admin()` registers, through Lynx's `@api` extension points:

- `AdminSlots::EDITOR_HEADER`: the Livewire component `stats-page-line` (`App\Stats\Admin\PageLine`, view `resources/views/stats/page-line.blade.php`);
- `HomeScreen::stat()`: views today;
- `HomeScreen::panel()`: `stats.home.top-pages` (main column) and `stats.home.top-files` (side column), built from the admin's own `x-lynx-admin::home.section` and `home.row` components. `App\Stats\Admin\TopLists` reads them, caches them for 5 minutes, and leaves out pages since deleted.

All of them show only to people who may edit pages (`AdminAccess::allows('pages.edit')`).

The admin's CSS is built in advance, so the app's admin views use only the admin's components and the Tailwind classes it already ships. The share bar under each row is a plain `bg-muted` track with a `bg-primary` fill and an inline width, the idea of BlatUI's meter without its package.

## Privacy

`stat_counts` holds no address, user agent, session, user, or referrer. The beacon's route sets no cookie and starts no session, and the download route's counting doesn't either. Addresses are used for one thing: the beacon's per-minute limit, as a hash, for a minute. The web server's own logs still record addresses, as they do for every request. The privacy page says all of this, in each language (`database/seeders/content/privacy.php`).

`tests/Feature/Stats/PrivacyTest.php` checks it: after a view, an event, and a download, no table holds the test's address or user agent.

## Testing with made-up history

```bash
php artisan stats:fake-history "template=download" --days=90 --seed=1
```

`App\Stats\FakeHistory` writes daily views and downloads that look like a real site's, for the pages a selector finds:

- nothing before a page's date field (`--date-field`, `published_on` by default);
- a launch bump in a page's first days, fading over two to three weeks;
- a long-tailed appeal per page (most get a few downloads a day, a handful get hundreds) and a shape: steady, rising, fading, popular two to three months ago, or a spike in the last week;
- quieter weekends (about 40%), a dip over the Christmas holidays, and a few newsletter days that lift many pages at once;
- each day's count drawn around its expected value (Poisson), and views at 8 to 20 times downloads;
- downloads shared among `--subjects` (the first gets most).

The same `--seed` gives the same history. It refuses to run in production without `--force`.

This site adds `php artisan site:seed-downloads --count=500`, which makes download pages with titles, kinds, and one or two languages, publication dates spread over two years (more in recent months, clustered in a few release weeks, a dozen in the last week), then their history, and fills the featured slots. The public demo resets every hour (`site:reset-demo`) and makes 60 of them, so its lists aren't empty.

## Adding it to another Lynx site

1. **Copy the generic part:**
   - `app/Stats/`
   - `config/stats.php`
   - `database/migrations/2026_11_25_000000_create_stat_tables.php`
   - `resources/views/components/stats-beacon.blade.php`
   - `resources/views/stats/`
   - `lang/en/stats.php` (and your other languages)
   - `tests/Feature/Stats/BeaconTest.php`, `DownloadLinksTest.php`, `PrivacyTest.php`, `FakeHistoryTest.php`, `AdminTest.php`, `helpers.php`, and `tests/Unit/StatsArchTest.php`. Adapt the helpers that pick a page and the owner account, and drop the `site:seed-downloads` test, which is this site's.
2. **Require the bot list:** `composer require jaybizzle/crawler-detect`.
3. **Register the provider** in `bootstrap/providers.php`: `App\Stats\StatsServiceProvider::class`.
4. **Run the migration:** `php artisan migrate`.
5. **Put the beacon in your layout,** before `</body>`: `<x-stats-beacon :page="$page" />`.
6. **Declare your events** in `config/stats.php`, and mark the elements with `data-stats-event`.
7. **Link files through the counter** where you offer downloads: `DownloadLinks::for($file, 'field_name', $expires, $subject)`. Public files can use it too; the redirect goes through Lynx's signed address either way.
8. **Sort a list by activity** where you want one: get the ids from a selector, order them with `Ranking::ids($ids, 'download')` (or `'view'`, or an event), cache the result as long as the page cache keeps the page, and load each page's slice with `lynx()->pages->find('id=1|2|3')`. Give the list's template a cache rule with its query parameters, 404 for invalid ones, and nofollow links.
9. **On SQLite, set a busy timeout** on the connection (`'busy_timeout' => 5000` in `config/database.php`), so concurrent counts wait instead of failing.
10. **Say it on the privacy page.**
11. **Check it:** `php artisan stats:fake-history "template=…"`, then the list and the admin home.

## Things that bit us

- **Session cookies from the beacon.** In the `web` middleware group, every beacon request started a session and set `laravel_session` and `XSRF-TOKEN` cookies, and a database session row kept the address and user agent. Registering the route outside the group fixed it.
- **An HttpOnly cookie read from JavaScript.** The first idea skipped signed-in editors in the script, by reading `lynx_bypass`. The browser hides HttpOnly cookies from scripts, so the server checks it instead.
- **WAL and `migrate:fresh`.** SQLite's WAL mode would let reads go on during a write, but `migrate:fresh` empties the database file, which breaks WAL's side files (`-wal`, `-shm`) under open connections. This demo resets with `migrate:fresh` every hour, so it keeps the default journal and only sets a busy timeout. A site that never empties its database file can turn WAL on.
- **Page cache rules replace each other.** `SavePageCacheRules` replaces every stored rule with the set it's given. A seeder that saved only the search page's rule wiped the home and download rules. Both seeders now save one shared list.
- **Too few cached copies.** A list with sorts and pages needs more copies than `max_variants` allows by default; past the limit, pages are rendered every time.
- **Throttling the file.** A throttle in front of the download redirect would give an office behind one address a 429 instead of the file. The download route is never throttled; counting once per link already bounds it.

## Later

- **Counts from elsewhere** (files on a CDN, numbers exported from another tool): call `PageStats::record(..., source: 'cdn')` from an import job, a CSV command, or an API endpoint with a token. Give each source its own name, so imported numbers never overwrite the site's and can be corrected alone.
- **A separate database:** set `STATS_DB_CONNECTION`. Tests then need `$connectionsToTransact` to include it.
- **Heavier traffic:** compact daily rows older than a year into monthly ones, and buffer hits in Redis with a scheduled flush, once measurements call for it.
- **A Lynx module:** `app/Stats` uses only Lynx's `@api` code and nothing else from this app (`tests/Unit/StatsArchTest.php` checks it), so it can become `lynxcms/stats` by renaming its namespace and turning its provider and config into the package's.
