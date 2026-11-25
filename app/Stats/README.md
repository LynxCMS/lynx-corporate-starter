# Visitor counts

Counts page views, downloads and the site's own events (such as a click on the home hero's button) as daily totals per page, with no personal data. It sorts pages by recent activity (Popular), shows counts in the page editor and on the admin home, and makes up realistic history for testing.

It is app code for now, written to move to another Lynx site, or into a Lynx module, without changes: it uses only Lynx's `@api` code and nothing else from this app (`tests/Unit/StatsArchTest.php`).

## What is counted

- **Views:** `<x-stats-beacon :page="$page" />` in the layout sends a beacon once a page has been visible for a second. Cached pages keep it, as it is the same for every visitor.
- **Downloads:** `DownloadLinks::for($file, $field, $expires, $subject)` gives a signed link that counts one download the first time it is opened, then redirects to the file. A second click and an email's link scanner don't count again.
- **The site's own events:** `data-stats-event="name"` on a link or button counts clicks, and on a `<details>` element, openings. `data-stats-subject` names what within the page. Only names listed in `config/stats.php` count.

Bots (crawler-detect's list), prefetches, signed-in editors, requests from other sites and unpublished pages aren't counted.

## Privacy

`stat_counts` holds daily totals per page, event, subject and source: no address, user agent, session, user or referrer. The beacon's route runs outside the `web` middleware group, so it starts no session and sets no cookie. Addresses are used for a minute at most, as a hash, to limit how many hits one address records.

## Reading the counts

- `PageStats`: `record()`, `totals()`, `daily()`, `top()`, `sum()`.
- `Ranking::ids($ids, 'download')`: the ids by recent activity, each day weighing half as much every `stats.half_life_days`.
- The editor's header line (`Admin\PageLine`) and the admin home's lists (`Admin\TopLists`, `resources/views/stats/home`).

## Moving it to another site

1. Copy `app/Stats`, `config/stats.php`, the migration `*_create_stat_tables.php`, `resources/views/components/stats-beacon.blade.php`, `resources/views/stats/`, `lang/*/stats.php` and `tests/Feature/Stats` with `tests/Unit/StatsArchTest.php`.
2. `composer require jaybizzle/crawler-detect`.
3. Add `App\Stats\StatsServiceProvider` to `bootstrap/providers.php` and run the migration.
4. Put `<x-stats-beacon :page="$page" />` before `</body>` in the layout.
5. On SQLite, set `journal_mode` to `wal` and a `busy_timeout` on the connection.

## Testing with made-up history

`php artisan stats:fake-history "template=post" --days=90 --seed=1` fills the history of the pages a selector finds: nothing before each page's date field, a launch bump, quieter weekends and holidays, newsletter days and Poisson noise.

## Later

- Counts from outside the site: `PageStats::record(..., source: 'cdn')` is the hook for an import job, an API or a CSV command.
- A separate database: set `STATS_DB_CONNECTION` to another connection.
- Compacting old rows and buffering writes, when traffic calls for it.
