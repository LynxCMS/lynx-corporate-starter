# Arcturus Systems, a Lynx demo

A home page in the style of a large enterprise site, built on [Lynx](https://lynxcms.org/cms/docs). The company, its products, clients and figures are invented.

The home page is a list of **sections** that editors add, sort and switch off in the page editor. Each section features **pages**: products, case studies, insights, news, training programs and promos. The hero is a carousel of slides, which are pages without a URL.

## Running it

```bash
composer install
npm install && npm run build
cp .env.example .env              # then set OWNER_PASSWORD
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
```

Herd serves it at `http://lynx-homepage.test`, and the admin is at `/lynx`. Sign in as `owner@arcturus.example` with the password from `.env`. The site is in English (`/`) and Portuguese (`/pt`).

`php artisan migrate:fresh --seed` starts over. It draws a picture for every page with GD, which takes a few seconds; `SITE_ARTWORK=false` skips them.

## How the home page is built

| Part | Where |
| --- | --- |
| The `sections` matrix and its section types | `database/seeders/SectionsSchemaSeeder.php` |
| One view per section type | `resources/views/templates/fields/sections/` |
| One card for every template | `resources/views/components/card.blade.php` |
| The hero's slides (`/homepage-hero/`) and promos (`/_promos/`), without a URL | `database/seeders/SiteSchemaSeeder.php` |
| The mega-menu and the footer (menus module) | `resources/views/menus/`, `database/seeders/SiteFeaturesSeeder.php` |
| Buttons, dots and autoplay of the carousels | `resources/js/carousel.js` |

**A new kind of section** is a new block type in `SectionsSchemaSeeder` (or in the admin, under the `sections` field) and a view named after its slug in `templates/fields/sections`.

**Cards** are pages an editor picks in a section's `cards` field, which is a page field. A card shows what its template has: a figure for products and case studies, a kind for insights and promos, a date for news.

**Sections leave out a page that is offline in the visitor's language** (`App\Support\Featured`). On its own, Lynx would still list it, linking to its default language URL.

**The hero prints every slide in the HTML.** The page cache can then store the whole page, and the hero works without JavaScript: slides scroll and snap. The summit slide unpublishes itself two weeks after seeding.

## Search and bookmarks

**Search.** The search icon in the header opens a panel with a search box and a list of suggestions. The list starts with the pages editors pick on the **Search** page (`suggested`, in their order), then the pages visitors opened most from search results lately, then the newest pages, until the Search page's "Pages in the search panel" is reached (`App\Search\Suggestions`).

Opening a result sends a beacon to `/search/hit`, which counts once per address and page a day and ignores bots (`SearchHitController`). Each visit adds `2^(time since the epoch / half-life)` to the page's score in `page_popularity`, so ordering by score ranks pages with older visits counting less, without rewriting old rows (`App\Search\Popularity`). The half-life is a week; set `SITE_POPULARITY_HALF_LIFE_DAYS` to change it.

**Bookmarks.** The bookmark button saves the page in the browser's localStorage, and the header's list icon counts them and links to **My bookmarks** (`/my-bookmarks`). That page asks `/bookmarks/cards` for the cards of the saved pages, in the visitor's language, and leaves out pages they can't see. **Share this list** makes a link (`?items=`) that opens the list on another device, with a button to save it there. Nothing about bookmarks is stored on the server.

On an existing database, add both with `php artisan migrate` and `php artisan db:seed --class=SearchAndBookmarksSeeder`.

## Checks

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

After changing fields, run `php artisan lynx:phpstan` to update `lynx/phpstan/types.php`. Menus are kept as page parts, so after changing their Blade views run `php artisan cache:clear`.
