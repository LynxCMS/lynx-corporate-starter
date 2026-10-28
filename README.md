# Arcturus Systems, a corporate starter for Lynx

A corporate website in the style of a large enterprise site, built on [Lynx CMS](https://lynxcms.org/cms/docs/) and Laravel: a home page made of sections that editors arrange, a mega-menu, a hero carousel, a search panel with suggestions, and bookmarks, in English and Portuguese. The company, its products, clients and figures are invented.

Install it to see how far a Lynx site can be arranged from the admin without a developer, and to read the code behind it. It takes about five minutes.

![The home page: a hero carousel, then rows of cards](docs/screenshots/home.png)

## What you can try

- **Rearrange the home page.** It is a list of sections (a hero, rows of cards, a feature with links, a newsletter). Drag them into another order, switch one off, or add one, then reload the site.
- **Pick what a section features.** A row of cards lists the pages an editor chose: products, case studies, insights, news, training programs or promos. Search for a page to add it, and drag it into place.
- **Schedule the hero.** Its slides are pages of their own, without a URL. One unpublishes itself two weeks after you install, and any slide can be given a time to go live or go offline.
- **Search.** The search icon opens a panel that shows results as you type, and suggestions: the pages editors pick first, then the pages visitors open most from search, then the newest.
- **Bookmark pages.** The bookmark icon saves a page in your browser, and **My bookmarks** lists them, with a link to share the list.
- **Switch languages.** Every page is in English (`/`) and Portuguese (`/pt`). Take a page offline in Portuguese and see the Portuguese home page leave it out.

## Screenshots

### The site

| | |
| --- | --- |
| ![The mega-menu, open on Products](docs/screenshots/mega-menu.png) | ![The search panel, with results under the field as you type and suggestions beside them](docs/screenshots/search-panel.png) |
| **The mega-menu.** Each section opens a panel whose columns list a page's children. | **The search panel.** Results show as you type; suggestions come from the Search page, the pages opened most from search, and the newest. |
| ![My bookmarks, with three saved pages](docs/screenshots/bookmarks.png) | <img src="docs/screenshots/phone-pt.png" alt="The home page in Portuguese on a phone" width="260"> |
| **My bookmarks.** Pages saved in your browser, with a link to share the list. | **On a phone, in Portuguese.** The menu folds away and the hero still scrolls. |

### The admin

| | |
| --- | --- |
| ![The page tree](docs/screenshots/admin-pages.png) | ![The home page's sections in the page editor](docs/screenshots/admin-home-sections.png) |
| **Pages.** The site's tree, with the hero's slides and the promos in branches of their own. | **The home page's sections.** Drag them into order, switch one off, clone or remove it. |
| ![A Cards section, open, with its pages in order, its layout and its background](docs/screenshots/admin-cards-section.png) | ![The section types of the sections field](docs/screenshots/admin-section-types.png) |
| **A Cards section.** Search for pages to feature, drag them into order, and choose a layout and a background. | **Section types.** Each is a block type of the `sections` field, rendered by its own Blade view. |

## What you need

- **PHP 8.5** with the `gd`, `pdo_sqlite`, `mbstring` and `fileinfo` extensions (most PHP installs have them). `php -v` and `php -m` tell you what you have.
- **Composer 2**, PHP's package manager ([getcomposer.org](https://getcomposer.org/)).
- **Node.js 20.19 or newer** (or 22.12 or newer), to build the CSS and JavaScript.
- **Git**.

The site keeps its data in SQLite, a database in a single file, so there is no database server to set up. [Laravel Herd](https://herd.laravel.com/) (macOS and Windows) gives you PHP, Composer and Node in one install.

## Installing it

**1. Get the code.**

```bash
git clone https://github.com/LynxCMS/lynx-comporate-starter.git arcturus
cd arcturus
```

**2. Install it.**

```bash
composer setup
```

This one command installs the PHP and JavaScript packages, creates the `.env` file (the site's settings) from `.env.example`, generates the app key, creates the SQLite database and its tables, links the folder where uploaded images live, and builds the CSS and JavaScript.

**3. Choose the owner's password.** Open `.env` and set `OWNER_PASSWORD`. The default, `change-me-now`, is fine on your own computer and nowhere else.

**4. Add the demo content.**

```bash
php artisan db:seed
```

This creates the owner account, installs Lynx, and adds the templates, the pages in both languages, the home page's sections, the menus and the search settings. It also draws an abstract picture for every page, which takes a few seconds.

**5. Start it.**

```bash
composer dev
```

Open [http://localhost:8000](http://localhost:8000). `composer dev` keeps running in that terminal; stop it with `Ctrl+C`. `php artisan serve` works too, if you only need the site.

**With Laravel Herd**, skip step 5: Herd serves the folder at `http://arcturus.test` (the folder's name, then `.test`). Set `APP_URL=http://arcturus.test` in `.env` so the images' addresses match.

## Signing in

The admin is at [http://localhost:8000/lynx](http://localhost:8000/lynx). Sign in as `owner@arcturus.example` with the password from `.env`. The owner is a superuser and may change everything.

A short tour:

1. **Pages**, then the home page: its **Sections** field lists the sections. Drag one, switch one off with **Switch off**, then **Save** and reload the site.
2. Open a **Cards** section and change its pages, its layout (four columns, three columns or a carousel) or its background.
3. **Pages**, then **Home page hero**: each slide's **Settings** tab holds its schedule.
4. **Pages**, then **Search**: **Suggested pages** come first in the search panel, in the order you drag them to, and **Pages in the search panel** sets how many show.
5. **Menus**: the mega-menu and the footer.
6. **Site texts**: the words of the site's frame that owners may change, such as the footer's note.

## Starting over

```bash
php artisan migrate:fresh --seed
```

This deletes everything, your changes included, and adds the demo content again. Add `SITE_ARTWORK=false` in `.env` first to skip drawing the pictures, which makes it faster.

## Other options

**PostgreSQL** instead of SQLite: create a database, and after step 2 set `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`. Run `php artisan migrate`, then carry on with step 3.

**The page cache** stores guests' pages as HTML and sends them again without rendering. It is off while you develop; set `LYNX_PAGE_CACHE=true` in `.env` to try it. The home page keeps for 15 minutes, since it lists the latest news. Signed-in editors always see fresh pages. See [Page Cache](https://lynxcms.org/cms/docs/modules/page-cache).

## When something goes wrong

| What you see | What to do |
| --- | --- |
| `Set OWNER_PASSWORD in .env before seeding.` | Set `OWNER_PASSWORD` in `.env`, then run `php artisan db:seed` again. |
| Pages show without images | Run `php artisan storage:link`, and check that `APP_URL` in `.env` is the address in your browser. |
| `Call to undefined function imagecreatetruecolor()` while seeding | PHP's `gd` extension is missing. Install it, or set `SITE_ARTWORK=false` to seed without pictures. |
| `Vite manifest not found` | Run `npm run build`, or keep `composer dev` running. |
| A menu doesn't show a change you made in its Blade view | Menus are kept as page parts: run `php artisan cache:clear`. |
| `Your requirements could not be resolved` from Composer | Check `php -v`: Lynx needs PHP 8.5. |
| Port 8000 is busy | Run `php artisan serve --port=8080` and set `APP_URL=http://localhost:8080`. |

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

**Sections leave out a page that is offline in the visitor's language** with Lynx's `publishedIn()`. On its own, Lynx would still list it, linking to its default language URL.

**The hero prints every slide in the HTML.** The page cache can then store the whole page, and the hero works without JavaScript: slides scroll and snap. The summit slide unpublishes itself two weeks after seeding.

## Search and bookmarks

**Search.** The search icon in the header opens a panel with a search box and a list of suggestions. As you type, the first matches show under the box (`/search/results`). The suggestions start with the pages editors pick on the **Search** page (`suggested`, in their order), then the pages visitors opened most from search results lately, then the newest pages, until the Search page's "Pages in the search panel" is reached (`App\Search\Suggestions`).

Opening a result sends a beacon to `/search/hit`, which counts once per address and page a day and ignores bots (`SearchHitController`). Each visit adds `2^(time since the epoch / half-life)` to the page's score in `page_popularity`, so ordering by score ranks pages with older visits counting less, without rewriting old rows (`App\Search\Popularity`). The half-life is a week; set `SITE_POPULARITY_HALF_LIFE_DAYS` to change it.

**Bookmarks.** The bookmark button saves the page in the browser's localStorage, and the header's list icon counts them and links to **My bookmarks** (`/my-bookmarks`). That page asks `/bookmarks/cards` for the cards of the saved pages, in the visitor's language, and leaves out pages they can't see. **Share this list** makes a link (`?items=`) that opens the list on another device, with a button to save it there. Nothing about bookmarks is stored on the server.

## Checks

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

After changing fields, run `php artisan lynx:phpstan` to update `lynx/phpstan/types.php`.

## Learn more

- [Lynx documentation](https://lynxcms.org/cms/docs/): templates and fields, selectors, languages, the admin and its modules.
- [Build a meeting rooms app](https://lynxcms.org/cms/docs/guides/meeting-rooms/), another starter, where Lynx is the base of an application.
