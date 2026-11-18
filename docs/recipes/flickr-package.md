# Sharing the Flickr Feature as a Package

[Photo Albums from Flickr](./flickr-thumbnails.md) built a feature inside one site. This recipe turns a bigger version of it into a Composer package that any Lynx site can install with one command.

The package adds a **Sync with Flickr** button to the admin. When an editor presses it, the package reads the Flickr account's albums and:

- creates a page for each album that has no page yet, with its title, album id, description, photo count and thumbnails, **unpublished**, so someone can look at it before visitors do;
- updates the thumbnails, photo count and description of album pages whose album changed on Flickr since the last sync, and leaves their titles to the editors;
- leaves alone the pages someone has open in the admin, and updates them on the next sync.

A scheduled command does the same every night.

New pages are unpublished pages. They are not drafts: in Lynx a draft holds pending changes to a page that already exists.

Every file below is named with the path where it is saved, relative to the site's root folder, and the same path is the first comment in its code. JSON has no comments, so `composer.json` files name their path in the line above them.

## 1. Creating the package folder

While you build the package, it lives in the site, in `packages/lynx-flickr`. Its `composer.json` names it, says which namespace maps to `src/`, and lists the Lynx packages it needs.

`packages/lynx-flickr/composer.json`:

```json
{
    "name": "acme/lynx-flickr",
    "description": "Flickr albums as Lynx pages: a Sync with Flickr button, a scheduled sync and thumbnails kept up to date.",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.5",
        "lynxcms/admin": "^0.1.41",
        "lynxcms/core": "^0.1.41"
    },
    "require-dev": {
        "orchestra/testbench": "^11.0",
        "pestphp/pest": "^5.3",
        "pestphp/pest-plugin-laravel": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "Acme\\LynxFlickr\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Acme\\LynxFlickr\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Acme\\LynxFlickr\\FlickrServiceProvider"
            ]
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "@php vendor/bin/testbench package:discover --ansi"
        ],
        "test": [
            "@php vendor/bin/pest"
        ]
    },
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- `extra.laravel.providers` lets Laravel find the package's service provider by itself, with no line in the site's code (Laravel calls this *package discovery*).
- `orchestra/testbench` runs the package's tests in a small Laravel app of their own (step 11). Its `post-autoload-dump` script runs package discovery for that app.

## 2. Installing it in the site

Tell the site's Composer where the package is. In the site's `composer.json`, add a `repositories` entry next to `require`:

The site's `composer.json`, excerpt:

```json
"repositories": [
    { "type": "path", "url": "packages/lynx-flickr" }
],
```

Then require it:

```bash
composer require acme/lynx-flickr:@dev
```

Composer links the folder into `vendor/acme/lynx-flickr`, so a change in `packages/lynx-flickr` is live at once. Other sites install it from a repository later (step 12).

## 3. Adding the config

The package reads its settings from `config('lynx-flickr...')`. Sites set them in `.env`, or publish the file with `php artisan vendor:publish --tag=lynx-flickr-config` to change it.

```php
<?php

// packages/lynx-flickr/config/lynx-flickr.php

declare(strict_types=1);

return [

    // The Flickr account whose albums become pages, and a key for Flickr's API.
    'api_key' => env('FLICKR_API_KEY'),
    'user_id' => env('FLICKR_USER_ID'),

    // The page that holds the album pages. flickr:install creates it under the home page.
    'parent' => env('FLICKR_PARENT', '/photos'),

    // How many new albums one sync turns into pages. The next sync takes the rest.
    'import_limit' => (int) env('FLICKR_IMPORT_LIMIT', 20),

];
```

The site's `.env`:

```ini
# .env
FLICKR_API_KEY=your-key-here
FLICKR_USER_ID=12345678@N00
```

## 4. Naming the templates and fields

A package shares the site's database with everything else, so its fields need names no site uses already. These start with `flickr_`. One class holds every name, so the rest of the code never repeats a string:

```php
<?php

// packages/lynx-flickr/src/FlickrSchema.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

/**
 * The names the package gives its templates, fields and permission. Field names start with
 * flickr_ so they never meet a field the site already has.
 */
final class FlickrSchema
{
    public const string ALBUM_TEMPLATE = 'flickr-album';

    public const string ALBUMS_TEMPLATE = 'flickr-albums';

    public const string ALBUM_ID = 'flickr_album_id';

    public const string DESCRIPTION = 'flickr_description';

    public const string PHOTO_COUNT = 'flickr_photo_count';

    public const string THUMBNAILS = 'flickr_thumbnails';

    public const string UPDATED_AT = 'flickr_updated_at';

    public const string PERMISSION = 'flickr.sync';

    /** @var array<string, array{type: string, label: string, translatable: bool}> */
    public const array FIELDS = [
        self::ALBUM_ID => ['type' => 'text', 'label' => 'Flickr album id', 'translatable' => false],
        self::DESCRIPTION => ['type' => 'textarea', 'label' => 'Album description', 'translatable' => true],
        self::PHOTO_COUNT => ['type' => 'integer', 'label' => 'Photos', 'translatable' => false],
        self::THUMBNAILS => ['type' => 'textarea', 'label' => 'Thumbnails', 'translatable' => false],
        self::UPDATED_AT => ['type' => 'integer', 'label' => 'Updated on Flickr', 'translatable' => false],
    ];
}
```

The fields match the first recipe's, with the `flickr_` prefix. `flickr_description` is translatable: Flickr writes the default language and editors translate the others. `flickr_updated_at` keeps the time Flickr last changed the album. The sync compares it with Flickr's to find the albums that changed.

## 5. Reading albums from Flickr

The client has three methods:

- `albums()` lists the account's albums. Flickr's list already gives each album's title, description, photo count and last change.
- `details()` reads the same for one album. The save hook uses it.
- `thumbnails()` reads one album's photos and builds their 150 pixel square addresses, as in the first recipe.

```php
<?php

// packages/lynx-flickr/src/Flickr/FlickrClient.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Flickr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Reads the configured account's albums and their photos from Flickr's REST API.
 *
 * Every method may also throw ConnectionException or RequestException when Flickr cannot be
 * reached or answers with an HTTP error.
 */
final readonly class FlickrClient
{
    private const string ENDPOINT = 'https://www.flickr.com/services/rest/';

    private const int TIMEOUT_SECONDS = 5;

    /** Flickr's largest page, for albums and for photos. */
    private const int PAGE_SIZE = 500;

    /**
     * Flickr's image addresses, from its photo source URL format. Size "q" is a 150 pixel square.
     * https://www.flickr.com/services/api/misc.urls.html
     */
    private const string IMAGE_URL = 'https://live.staticflickr.com/%s/%s_%s_q.jpg';

    /** Flickr's error code for an album id it does not know. */
    private const int ALBUM_NOT_FOUND = 1;

    /**
     * The account's albums, in the order the account owner set on Flickr.
     *
     * @return list<Album>
     *
     * @throws FlickrFailed|ConnectionException|RequestException
     */
    public function albums(): array
    {
        $response = $this->call('flickr.photosets.getList', ['per_page' => self::PAGE_SIZE]);

        if ($response->json('stat') !== 'ok') {
            throw FlickrFailed::because('flickr.photosets.getList', (string) $response->json('message'));
        }

        /** @var list<array<string, mixed>> $albums */
        $albums = $response->json('photosets.photoset', []);

        return array_map($this->album(...), $albums);
    }

    /**
     * One album's title, description, photo count and last change.
     *
     * @throws AlbumNotFound|FlickrFailed|ConnectionException|RequestException
     */
    public function details(string $albumId): Album
    {
        $response = $this->call('flickr.photosets.getInfo', ['photoset_id' => $albumId]);
        $this->assertFound($response, $albumId, 'flickr.photosets.getInfo');

        /** @var array<string, mixed> $album */
        $album = $response->json('photoset', []);

        return $this->album($album);
    }

    /**
     * One album's thumbnail addresses, in the album's order.
     *
     * @return list<string>
     *
     * @throws AlbumNotFound|FlickrFailed|ConnectionException|RequestException
     */
    public function thumbnails(string $albumId): array
    {
        $response = $this->call('flickr.photosets.getPhotos', ['photoset_id' => $albumId, 'per_page' => self::PAGE_SIZE]);
        $this->assertFound($response, $albumId, 'flickr.photosets.getPhotos');

        /** @var list<array<string, mixed>> $photos */
        $photos = $response->json('photoset.photo', []);

        return array_map(fn (array $photo): string => sprintf(
            self::IMAGE_URL,
            $this->text($photo, 'server'),
            $this->text($photo, 'id'),
            $this->text($photo, 'secret'),
        ), $photos);
    }

    /**
     * getList and getInfo describe an album the same way.
     *
     * @param  array<string, mixed>  $album
     */
    private function album(array $album): Album
    {
        return new Album(
            id: $this->text($album, 'id'),
            title: $this->text($album, 'title._content'),
            // Flickr allows a few HTML tags in descriptions; pages keep the text only.
            description: trim(strip_tags($this->text($album, 'description._content'))),
            photoCount: (int) data_get($album, 'count_photos', 0),
            updatedAt: (int) data_get($album, 'date_update', 0),
        );
    }

    /** @throws AlbumNotFound|FlickrFailed */
    private function assertFound(Response $response, string $albumId, string $method): void
    {
        if ($response->json('stat') === 'ok') {
            return;
        }

        throw $response->json('code') === self::ALBUM_NOT_FOUND
            ? AlbumNotFound::withId($albumId)
            : FlickrFailed::because($method, (string) $response->json('message'));
    }

    /**
     * @param  array<string, string|int>  $parameters
     *
     * @throws ConnectionException|RequestException
     */
    private function call(string $method, array $parameters): Response
    {
        return Http::timeout(self::TIMEOUT_SECONDS)->get(self::ENDPOINT, [
            'method' => $method,
            'api_key' => config('lynx-flickr.api_key'),
            'user_id' => config('lynx-flickr.user_id'),
            'format' => 'json',
            'nojsoncallback' => 1,
            ...$parameters,
        ])->throw();
    }

    /** @param  array<string, mixed>  $data */
    private function text(array $data, string $key): string
    {
        $value = data_get($data, $key);

        return is_string($value) || is_int($value) ? (string) $value : '';
    }
}
```

An album from the list:

```php
<?php

// packages/lynx-flickr/src/Flickr/Album.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Flickr;

/** One of the account's albums, as Flickr describes it. */
final readonly class Album
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public int $photoCount,
        public int $updatedAt,
    ) {}
}
```

And the two ways Flickr can say no:

```php
<?php

// packages/lynx-flickr/src/Flickr/AlbumNotFound.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Flickr;

use RuntimeException;

/** Flickr answered, but has no album with this id for the configured account. */
final class AlbumNotFound extends RuntimeException
{
    public static function withId(string $albumId): self
    {
        return new self("Flickr has no album [{$albumId}].");
    }
}
```

```php
<?php

// packages/lynx-flickr/src/Flickr/FlickrFailed.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Flickr;

use RuntimeException;

/** Flickr refused a request, for example because the API key or the account is wrong. */
final class FlickrFailed extends RuntimeException
{
    public static function because(string $method, string $message): self
    {
        return new self("Flickr refused [{$method}]: {$message}");
    }
}
```

## 6. Filling the page on every save

The hook is the one from the first recipe, with the package's names. It also writes `flickr_updated_at`, so the next sync knows the page is current. A sync saves pages with their thumbnails already in the values, so the hook skips those saves and Flickr is asked once per album.

```php
<?php

// packages/lynx-flickr/src/FillFromFlickr.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

use Acme\LynxFlickr\Flickr\Album;
use Acme\LynxFlickr\Flickr\AlbumNotFound;
use Acme\LynxFlickr\Flickr\FlickrClient;
use Acme\LynxFlickr\Flickr\FlickrFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Pages\Page;
use Stringable;

/**
 * A before hook on pages.save: when an album page is saved, it asks Flickr for the album and adds
 * to the same save its thumbnails, photo count, last change and description (in the default
 * language; editors translate it). The album's title goes in only when the page has none.
 * A save that brings its own thumbnails, such as a sync, is left as it is.
 */
final readonly class FillFromFlickr
{
    public function __construct(private FlickrClient $flickr) {}

    public function __invoke(Input $input): Input
    {
        $page = $input->get('page');

        return $page instanceof Page ? $this->fillPage($page, $input) : $this->fillValues($input);
    }

    /** $page->save(): the hook sets the fields on the page itself. */
    private function fillPage(Page $page, Input $input): Input
    {
        if ($page->template()->name !== FlickrSchema::ALBUM_TEMPLATE) {
            return $input;
        }

        $fetched = $this->fetch($this->albumId($page->get(FlickrSchema::ALBUM_ID)), $page->id);

        if ($fetched === null) {
            return $input;
        }

        [$album, $thumbnails] = $fetched;
        $language = (string) lynx()->languages->default()->code;
        $page->setLanguageValue($language, FlickrSchema::THUMBNAILS, $thumbnails)
            ->setLanguageValue($language, FlickrSchema::PHOTO_COUNT, $album->photoCount)
            ->setLanguageValue($language, FlickrSchema::UPDATED_AT, $album->updatedAt)
            ->setLanguageValue($language, FlickrSchema::DESCRIPTION, $album->description);

        if ($this->isBlank($page->getLanguageValue($language, 'title'))) {
            $page->setLanguageValue($language, 'title', $album->title);
        }

        return $input;
    }

    /** An id and the changed values (the admin, the API, MCP clients, drafts, syncs). */
    private function fillValues(Input $input): Input
    {
        $id = $input->int('id');
        $stored = $id === null ? null : lynx()->pages->get($id);
        $template = $stored?->template()->name ?? $input->string('template');

        if ($template !== FlickrSchema::ALBUM_TEMPLATE || $input->has('values.'.FlickrSchema::THUMBNAILS)) {
            return $input;
        }

        // The admin sends only the fields that changed, so the album id may be the stored one.
        $key = 'values.'.FlickrSchema::ALBUM_ID;
        $albumId = $input->has($key) ? $input->get($key) : $stored?->get(FlickrSchema::ALBUM_ID);
        $fetched = $this->fetch($this->albumId($albumId), $id);

        return $fetched === null ? $input : $this->withAlbum($input, $fetched[0], $fetched[1], $stored);
    }

    private function withAlbum(Input $input, Album $album, string $thumbnails, ?Page $stored): Input
    {
        $language = (string) lynx()->languages->default()->code;
        $title = $input->has("values.title.{$language}") ? $input->get("values.title.{$language}") : $stored?->getLanguageValue($language, 'title');

        $input = $input->with('values.'.FlickrSchema::THUMBNAILS, $thumbnails)
            ->with('values.'.FlickrSchema::PHOTO_COUNT, $album->photoCount)
            ->with('values.'.FlickrSchema::UPDATED_AT, $album->updatedAt)
            ->with('values.'.FlickrSchema::DESCRIPTION.".{$language}", $album->description);

        return $this->isBlank($title) ? $input->with("values.title.{$language}", $album->title) : $input;
    }

    /**
     * The album and its thumbnails, one address per line, or null when there is nothing to change.
     *
     * @return array{0: Album, 1: string}|null
     */
    private function fetch(?string $albumId, ?int $pageId): ?array
    {
        if ($albumId === null) {
            return null;
        }

        try {
            return [$this->flickr->details($albumId), implode("\n", $this->flickr->thumbnails($albumId))];
        } catch (AlbumNotFound $notFound) {
            throw ValidationException::withMessages(['values.'.FlickrSchema::ALBUM_ID => $notFound->getMessage()]);
        } catch (FlickrFailed|ConnectionException|RequestException $failure) {
            Log::warning('Flickr did not answer; the album was not refreshed.', [
                'page' => $pageId, 'album' => $albumId, 'error' => $failure->getMessage(),
            ]);

            return null;
        }
    }

    /** A page's field reads as an object that prints as its value, so cast it to a string first. */
    private function albumId(mixed $value): ?string
    {
        $albumId = is_string($value) || $value instanceof Stringable ? trim((string) $value) : '';

        return $albumId === '' ? null : $albumId;
    }

    private function isBlank(mixed $value): bool
    {
        return ! is_string($value) || trim($value) === '';
    }
}
```

A package cannot rely on the site's generated field types, so it writes the fields with `setLanguageValue()` instead of `$page->flickr_thumbnails = ...`.

## 7. Adding the install command

`flickr:install` creates the fields, the two templates and the page that holds the albums. It checks before it adds, so running it again changes nothing, and it stops when the site already has a field with one of these names and another type.

```php
<?php

// packages/lynx-flickr/src/Console/InstallCommand.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Console;

use Acme\LynxFlickr\FlickrSchema;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use LynxCms\Core\Support\Value;

/**
 * Adds the package's fields, its two templates and the page that holds the albums. Running it
 * again changes nothing, so it is safe in a deploy script.
 */
#[Signature('flickr:install')]
#[Description('Add the Flickr album fields, templates and albums page')]
final class InstallCommand extends Command
{
    public function handle(): int
    {
        $conflict = $this->conflictingField();

        if ($conflict !== null) {
            $this->error($conflict);

            return self::FAILURE;
        }

        $this->addFields();
        $this->addTemplates();
        $this->addAlbumsPage();
        $this->info('Flickr albums are installed. Open '.Value::string(config('lynx-flickr.parent')).' in the admin and press Sync with Flickr.');

        return self::SUCCESS;
    }

    /** A field the site already has under one of the package's names, with another type. */
    private function conflictingField(): ?string
    {
        foreach (FlickrSchema::FIELDS as $name => $field) {
            $existing = lynx()->fields->find($name);

            if ($existing !== null && ($existing->type !== $field['type'] || $existing->isTranslatable() !== $field['translatable'])) {
                $needs = $field['type'].($field['translatable'] ? ', translatable' : ', shared');

                return "The site already has a field [{$name}] with other settings; the Flickr package needs [{$needs}].";
            }
        }

        return null;
    }

    private function addFields(): void
    {
        foreach (FlickrSchema::FIELDS as $name => $field) {
            if (lynx()->fields->find($name) === null) {
                lynx()->fields->add($name, $field['type'], translatable: $field['translatable'], label: $field['label']);
            }
        }
    }

    private function addTemplates(): void
    {
        if (lynx()->templates->find(FlickrSchema::ALBUM_TEMPLATE) === null) {
            lynx()->templates->add(FlickrSchema::ALBUM_TEMPLATE, fields: ['title', ...array_keys(FlickrSchema::FIELDS)], label: 'Flickr album');
            lynx()->templates->update(FlickrSchema::ALBUM_TEMPLATE, ['allow_children' => false, 'parent_templates' => [FlickrSchema::ALBUMS_TEMPLATE]]);
        }

        if (lynx()->templates->find(FlickrSchema::ALBUMS_TEMPLATE) === null) {
            lynx()->templates->add(FlickrSchema::ALBUMS_TEMPLATE, fields: ['title'], label: 'Flickr albums', singleton: true);
            lynx()->templates->update(FlickrSchema::ALBUMS_TEMPLATE, ['allow_children' => true, 'child_templates' => [FlickrSchema::ALBUM_TEMPLATE]]);
        }
    }

    /** The albums page, under the home page, named after the last part of the configured path. */
    private function addAlbumsPage(): void
    {
        $path = Value::string(config('lynx-flickr.parent'));

        if (lynx()->pages->get($path) !== null) {
            return;
        }

        $page = lynx()->pages->new(FlickrSchema::ALBUMS_TEMPLATE, '/');
        $page->name = basename($path);
        $page->title = 'Photos';
        lynx()->pages->save($page);
    }
}
```

The `flickr-albums` template allows only album pages under it, and album pages may live only there.

## 8. Working out what to sync

A sync has two halves. The first one only reads: it compares Flickr's albums with the album pages and fetches the thumbnails it needs. It runs before anything is written, so no database transaction waits on Flickr.

```php
<?php

// packages/lynx-flickr/src/AlbumSync.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

use Acme\LynxFlickr\Flickr\Album;
use Acme\LynxFlickr\Flickr\AlbumNotFound;
use Acme\LynxFlickr\Flickr\FlickrClient;
use Acme\LynxFlickr\Flickr\FlickrFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * Compares the account's albums on Flickr with the album pages, and fetches the thumbnails of
 * the albums that are new or changed. Flickr's list already gives each album's title,
 * description, photo count and last change. It only reads: flickr.sync_albums writes the result. All
 * of Flickr's answers arrive here, before any database transaction starts.
 */
final readonly class AlbumSync
{
    public function __construct(private FlickrClient $flickr) {}

    /**
     * @throws AlbumNotFound|FlickrFailed|ConnectionException|RequestException
     */
    public function plan(): SyncPlan
    {
        $pages = $this->albumPages();
        $new = $updates = $skipped = [];

        foreach ($this->flickr->albums() as $album) {
            $page = $pages[$album->id] ?? null;

            if ($page === null) {
                $new = $this->addNew($new, $album);

                continue;
            }

            if (! $this->changedSince($page, $album)) {
                continue;
            }

            if (lynx()->pages->editLock($page) !== null) {
                $skipped[] = (int) $page->id;

                continue;
            }

            $updates[] = $this->update($page, $album);
        }

        return new SyncPlan($new, $updates, $skipped);
    }

    /**
     * Every album page, trashed and unpublished ones included, by album id. An album whose page
     * an editor trashed is not imported again.
     *
     * @return array<string, Page>
     */
    private function albumPages(): array
    {
        $pages = [];

        foreach (lynx()->pages->find('template='.FlickrSchema::ALBUM_TEMPLATE.', include=all') as $page) {
            $pages[(string) $page->get(FlickrSchema::ALBUM_ID)] = $page;
        }

        return $pages;
    }

    /**
     * @param  list<array{album: Album, thumbnails: string}>  $new
     * @return list<array{album: Album, thumbnails: string}>
     */
    private function addNew(array $new, Album $album): array
    {
        if (count($new) >= Value::int(config('lynx-flickr.import_limit'), 20)) {
            return $new;
        }

        return [...$new, ['album' => $album, 'thumbnails' => $this->thumbnails($album)]];
    }

    /** Flickr changed the album after the page last took its thumbnails. */
    private function changedSince(Page $page, Album $album): bool
    {
        return $album->updatedAt > Value::int((string) $page->get(FlickrSchema::UPDATED_AT));
    }

    private function update(Page $page, Album $album): AlbumUpdate
    {
        return new AlbumUpdate((int) $page->id, $album, $this->thumbnails($album));
    }

    private function thumbnails(Album $album): string
    {
        return implode("\n", $this->flickr->thumbnails($album->id));
    }
}
```

An album needs work when:

- **no page holds it**: it becomes a new page, up to `import_limit` per sync;
- **Flickr changed it after the page's `flickr_updated_at`**: its page gets the new thumbnails, photo count and description;
- otherwise it is skipped, and Flickr is not asked for its photos.

A page that someone has open in the admin holds an *edit lock*. Saving it from the sync would make the editor's next save fail, so the sync leaves it for next time.

The result is a plan:

```php
<?php

// packages/lynx-flickr/src/SyncPlan.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

use Acme\LynxFlickr\Flickr\Album;

/** What a sync will write: pages to create, pages to update, and pages left for the next sync. */
final readonly class SyncPlan
{
    /**
     * @param  list<array{album: Album, thumbnails: string}>  $new
     * @param  list<AlbumUpdate>  $updates
     * @param  list<int>  $skipped  ids of pages someone is editing
     */
    public function __construct(
        public array $new,
        public array $updates,
        public array $skipped,
    ) {}

    public function isEmpty(): bool
    {
        return $this->new === [] && $this->updates === [];
    }

    /**
     * The input of flickr.sync_albums.
     *
     * @return array{new: list<array<string, string|int>>, updates: list<array<string, string|int>>}
     */
    public function toInput(): array
    {
        return [
            'new' => array_map(fn (array $entry): array => [
                'id' => $entry['album']->id,
                'title' => $entry['album']->title,
                ...$this->albumData($entry['album'], $entry['thumbnails']),
            ], $this->new),
            'updates' => array_map(fn (AlbumUpdate $update): array => [
                'page' => $update->pageId,
                ...$this->albumData($update->album, $update->thumbnails),
            ], $this->updates),
        ];
    }

    /** @return array{description: string, photo_count: int, updated_at: int, thumbnails: string} */
    private function albumData(Album $album, string $thumbnails): array
    {
        return [
            'description' => $album->description,
            'photo_count' => $album->photoCount,
            'updated_at' => $album->updatedAt,
            'thumbnails' => $thumbnails,
        ];
    }
}
```

```php
<?php

// packages/lynx-flickr/src/AlbumUpdate.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

use Acme\LynxFlickr\Flickr\Album;

/** New values for an album page whose album changed on Flickr. */
final readonly class AlbumUpdate
{
    public function __construct(
        public int $pageId,
        public Album $album,
        public string $thumbnails,
    ) {}
}
```

## 9. Writing the changes in one action

The second half writes. It is an action of its own, `flickr.sync_albums`, so it gets what every Lynx action has: validation, a permission check, one database transaction, dry runs and the activity log.

```php
<?php

// packages/lynx-flickr/src/Actions/SyncAlbums.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Actions;

use Acme\LynxFlickr\FlickrSchema;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Action;
use LynxCms\Core\Actions\Pages\SaveDraft;
use LynxCms\Core\Actions\Pages\SavePage;
use LynxCms\Core\Actions\Runner\ActionContext;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;

/**
 * Writes what AlbumSync found: a new, unpublished page for each new album, and new thumbnails,
 * photo count and description for each changed one, on the live page and on its draft. Publishing a draft writes all of the
 * draft's values back to the page, so a draft with the old thumbnails would bring them back.
 */
final class SyncAlbums extends Action
{
    public static function name(): string
    {
        return 'flickr.sync_albums';
    }

    public static function description(): string
    {
        return 'Create unpublished pages for new Flickr albums and update the changed ones.';
    }

    public static function permission(): string
    {
        return FlickrSchema::PERMISSION;
    }

    public static function rules(): array
    {
        return [
            'new' => ['present', 'array'],
            'new.*.id' => ['required', 'string', 'regex:/^\d+$/'],
            'new.*.title' => ['present', 'string'],
            'updates' => ['present', 'array'],
            'updates.*.page' => ['required', 'integer'],
            '*.*.description' => ['present', 'string'],
            '*.*.photo_count' => ['required', 'integer'],
            '*.*.updated_at' => ['required', 'integer'],
            '*.*.thumbnails' => ['present', 'string'],
        ];
    }

    public static function schema(JsonSchema $schema): array
    {
        return [];
    }

    /** Its input is what Flickr answered, so AI tools have no use for it. */
    public static function exposedAsTool(): bool
    {
        return false;
    }

    /** One run touches many pages: each gets its own entry in its history. */
    public static function logsActivityPerPage(): bool
    {
        return true;
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function handle(Input $input, ActionContext $context): array
    {
        $created = $updated = 0;

        foreach ($input->array('new') as $album) {
            $created += $this->create(Value::map($album), $context) ? 1 : 0;
        }

        foreach ($input->array('updates') as $update) {
            $updated += $this->update(Value::map($update), $context) ? 1 : 0;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /** @param  array<string, mixed>  $album */
    private function create(array $album, ActionContext $context): bool
    {
        $albumId = Value::string($album['id']);

        if (lynx()->pages->findOne('template='.FlickrSchema::ALBUM_TEMPLATE.", flickr_album_id={$albumId}, include=all") !== null) {
            return false;
        }

        $parent = $this->parent();
        $context->authorize('pages.create', $parent, lynx()->templates->get(FlickrSchema::ALBUM_TEMPLATE));
        $language = $this->language();
        $title = Value::string($album['title']) !== '' ? Value::string($album['title']) : "Album {$albumId}";

        $context->run(SavePage::class, [
            'template' => FlickrSchema::ALBUM_TEMPLATE,
            'parent' => (string) $parent->id,
            'names' => [$language => $this->freeName($parent, $title, $albumId)],
            'values' => ['title' => [$language => $title], FlickrSchema::ALBUM_ID => $albumId] + $this->albumValues($album),
            'status' => ['unpublished'],
        ]);

        return true;
    }

    /** @param  array<string, mixed>  $update */
    private function update(array $update, ActionContext $context): bool
    {
        $page = lynx()->pages->get(Value::int($update['page']));

        // Someone opened the page since the sync looked: the next sync updates it.
        if ($page === null || lynx()->pages->editLock($page) !== null) {
            return false;
        }

        $context->authorize('pages.edit', $page);
        $values = $this->albumValues($update);
        $context->run(SavePage::class, ['id' => $page->id, 'values' => $values]);

        if (lynx()->pages->draft($page) !== null) {
            $context->run(SaveDraft::class, ['page' => $page->id, 'values' => $values]);
        }

        return true;
    }

    /**
     * The values a sync writes, the description in the default language. They always include
     * the thumbnails, so the save hook does not ask Flickr again. Titles are left to editors.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function albumValues(array $data): array
    {
        return [
            FlickrSchema::THUMBNAILS => Value::string($data['thumbnails']),
            FlickrSchema::PHOTO_COUNT => Value::int($data['photo_count']),
            FlickrSchema::UPDATED_AT => Value::int($data['updated_at']),
            FlickrSchema::DESCRIPTION => [$this->language() => Value::string($data['description'])],
        ];
    }

    private function language(): string
    {
        return (string) lynx()->languages->default()->code;
    }

    private function parent(): Page
    {
        $path = Value::string(config('lynx-flickr.parent'));

        return lynx()->pages->get($path)
            ?? throw ValidationException::withMessages(['parent' => "The albums page [{$path}] does not exist. Run php artisan flickr:install."]);
    }

    /** The title as a URL name, or the title plus the album id when a sibling has that name. */
    private function freeName(Page $parent, string $title, string $albumId): string
    {
        $name = Str::slug($title) !== '' ? Str::slug($title) : 'album';
        $isTaken = lynx()->pages->findOne("parent={$parent->id}, name={$name}, include=all") !== null;

        return $isTaken ? "{$name}-{$albumId}" : $name;
    }
}
```

- **New albums** become pages under the albums page, unpublished, named after their title. When a sibling already has that name, the album id is added to it.
- **Changed albums** get new thumbnails, photo count and description (in the default language) on the page and on its draft, if it has one. Publishing a draft writes all of the draft's values back to the page, so a draft with the old values would bring them back.
- **Titles** are written only for new pages. Once a page exists, its title belongs to the editors.
- `logsActivityPerPage()` gives each page its own entry in its history.
- `exposedAsTool()` keeps the action out of the MCP server and the AI agent. Its input is what Flickr answered, which an agent has no reason to invent.

Inside an action, other actions run with `$context->run()`. They share its transaction, so either every page of a sync is written or none is.

## 10. Adding the button and the scheduled sync

The button sits in the page editor's header, beside the save buttons, on the albums page only:

```php
<?php

// packages/lynx-flickr/src/Admin/SyncButton.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Admin;

use Acme\LynxFlickr\Actions\SyncAlbums;
use Acme\LynxFlickr\AlbumSync;
use Acme\LynxFlickr\Flickr\AlbumNotFound;
use Acme\LynxFlickr\Flickr\FlickrFailed;
use Acme\LynxFlickr\FlickrSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use LynxCms\Admin\AdminAccess;
use LynxCms\Core\Support\Value;

/**
 * The Sync with Flickr button in the page editor's header, on the albums page. It asks Flickr
 * first, then writes everything in one action, and tells the editor what changed.
 */
final class SyncButton extends Component
{
    #[Locked]
    public int $pageId;

    /** Every click is a new request, so the permission is checked on each one. */
    public function boot(): void
    {
        abort_unless(app(AdminAccess::class)->allows(FlickrSchema::PERMISSION), 403);
    }

    public function mount(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function sync(AlbumSync $sync): void
    {
        try {
            $plan = $sync->plan();
        } catch (AlbumNotFound|FlickrFailed|ConnectionException|RequestException $failure) {
            report($failure);
            $this->notify(__('lynx-flickr::admin.failed'), 'error');

            return;
        }

        if ($plan->isEmpty() && $plan->skipped === []) {
            $this->notify(__('lynx-flickr::admin.up_to_date'));

            return;
        }

        $counts = Value::map(lynx()->run(SyncAlbums::class, $plan->toInput())->value);
        $this->notify(__('lynx-flickr::admin.synced', [
            'created' => Value::int($counts['created'] ?? 0),
            'updated' => Value::int($counts['updated'] ?? 0),
            'skipped' => count($plan->skipped),
        ]));
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-lynx-admin::ui.button variant="outline" wire:click="sync" wire:loading.attr="disabled">
                    {{ __('lynx-flickr::admin.sync') }}
                </x-lynx-admin::ui.button>
            </div>
            BLADE;
    }

    /** The admin's notice at the bottom of the screen. */
    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('lynx-notify', message: $message, type: $type);
    }
}
```

`boot()` checks the permission on every click, because each click is a new request. The `lynx-notify` event shows the admin's usual notice at the bottom of the screen.

Its wording lives in a language file, so a site can translate it:

```php
<?php

// packages/lynx-flickr/lang/en/admin.php

declare(strict_types=1);

return [
    'sync' => 'Sync with Flickr',
    'synced' => ':created new album(s) as unpublished pages, :updated updated, :skipped skipped while being edited.',
    'up_to_date' => 'Everything is up to date with Flickr.',
    'failed' => 'Flickr could not be reached. Nothing changed. Try again in a few minutes.',
];
```

The command does the same as the button, for the scheduler:

```php
<?php

// packages/lynx-flickr/src/Console/SyncCommand.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Console;

use Acme\LynxFlickr\Actions\SyncAlbums;
use Acme\LynxFlickr\AlbumSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use LynxCms\Core\Support\Value;

/** The scheduler's way to press Sync with Flickr. */
#[Signature('flickr:sync')]
#[Description('Create pages for new Flickr albums and update the changed ones')]
final class SyncCommand extends Command
{
    public function handle(AlbumSync $sync): int
    {
        $plan = $sync->plan();
        $counts = Value::map(lynx()->run(SyncAlbums::class, $plan->toInput())->value);
        $created = Value::int($counts['created'] ?? 0);
        $updated = Value::int($counts['updated'] ?? 0);
        $skipped = count($plan->skipped);

        $this->info("Flickr sync: {$created} new, {$updated} updated, {$skipped} skipped while being edited.");

        return self::SUCCESS;
    }
}
```

The service provider puts it all together:

```php
<?php

// packages/lynx-flickr/src/FlickrServiceProvider.php

declare(strict_types=1);

namespace Acme\LynxFlickr;

use Acme\LynxFlickr\Actions\SyncAlbums;
use Acme\LynxFlickr\Admin\SyncButton;
use Acme\LynxFlickr\Console\InstallCommand;
use Acme\LynxFlickr\Console\SyncCommand;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\AdminSlots;
use LynxCms\Core\Actions\Pages\SavePage;
use LynxCms\Core\Pages\Page;

/** Flickr albums as Lynx pages. Laravel finds this provider through composer.json. */
final class FlickrServiceProvider extends ServiceProvider
{
    private const string CONFIG = __DIR__.'/../config/lynx-flickr.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG, 'lynx-flickr');
    }

    public function boot(): void
    {
        $this->publishes([self::CONFIG => config_path('lynx-flickr.php')], 'lynx-flickr-config');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'lynx-flickr');

        // Album pages take their thumbnails, photo count and description from Flickr on every save, whoever saves them.
        lynx()->hook(SavePage::class)->before($this->app->make(FillFromFlickr::class)(...));
        lynx()->actions()->register(SyncAlbums::class);
        lynx()->permissions()->register(FlickrSchema::PERMISSION, 'Sync photo albums with Flickr', requires: ['pages.view']);

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, SyncCommand::class]);
        }

        // The admin is optional: without it, flickr:sync still works.
        if (class_exists(AdminSlots::class)) {
            $this->registerAdmin();
        }
    }

    private function registerAdmin(): void
    {
        Livewire::component('lynx-flickr.sync-button', SyncButton::class);

        $this->app->make(AdminSlots::class)->add(
            AdminSlots::EDITOR_HEADER,
            'lynx-flickr.sync-button',
            fn (Page $page): bool => $page->template()->name === FlickrSchema::ALBUMS_TEMPLATE
                && $this->app->make(AdminAccess::class)->allows(FlickrSchema::PERMISSION),
        );
    }
}
```

- The `flickr.sync` permission appears in the admin's **Roles** screen. Give it to the roles that may press the button. Superusers have it already.
- The admin is optional. A site without it still gets `flickr:sync`.

### Showing album pages

The package leaves the look of album pages to each site. Copy step 6 of the [first recipe](./flickr-thumbnails.md#6-showing-the-album) into `resources/views/templates/flickr-album.blade.php`, with the package's field names (`flickr_description`, `flickr_photo_count`, `flickr_thumbnails`), and read the account id for the links from `config('lynx-flickr.user_id')`.

Schedule the sync in the site's `routes/console.php`:

```php
// routes/console.php (excerpt)
use Illuminate\Support\Facades\Schedule;

Schedule::command('flickr:sync')->dailyAt('03:00');
```

Then install it and try it:

```bash
php artisan flickr:install
```

Open `/photos` in the admin and press **Sync with Flickr**. The albums appear under it as unpublished pages. Publish the ones you want visitors to see.

## 11. Testing the package

The package's tests run in a Laravel app that Testbench builds for them, so they need no site. Package discovery loads Lynx and the packages it depends on, as a site would.

```php
<?php

// packages/lynx-flickr/tests/TestCase.php

declare(strict_types=1);

namespace Acme\LynxFlickr\Tests;

use Acme\LynxFlickr\FlickrServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * A small Laravel app for the package's tests. Package discovery loads Lynx and everything it
 * needs, as a real site would.
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [FlickrServiceProvider::class];
    }

    /** Livewire encrypts its snapshots, so the test app needs a key of its own. */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /** Lynx's roles are given to users, so the tests need Laravel's users table. */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }
}
```

```php
<?php

// packages/lynx-flickr/tests/Pest.php

declare(strict_types=1);

use Acme\LynxFlickr\Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);
```

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- packages/lynx-flickr/phpunit.xml -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Flickr">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

The tests fake Flickr with `Http::fake()`. `fakeFlickr()` can be called again in a test to change what Flickr answers, for example to add a photo between two syncs.

```php
<?php

// packages/lynx-flickr/tests/SyncAlbumsTest.php

declare(strict_types=1);

use Acme\LynxFlickr\Actions\SyncAlbums;
use Acme\LynxFlickr\Admin\SyncButton;
use Acme\LynxFlickr\AlbumSync;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use LynxCms\Core\Actions\Pages\LockEdit;
use LynxCms\Core\Actions\Pages\SavePage;
use LynxCms\Core\Actions\Roles\AssignRoles;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageStatus;

beforeEach(function (): void {
    config(['lynx-flickr.api_key' => 'test-key', 'lynx-flickr.user_id' => '12345678@N00']);
    $this->artisan('lynx:install', ['--no-interaction' => true]);
    $this->artisan('flickr:install')->assertSuccessful();
});

/**
 * Fakes Flickr: the account's albums (id => [title, last update, description]) and each album's
 * photo ids. Calling it again changes what the fake answers from then on.
 *
 * @param  array<string, array{0: string, 1: int, 2?: string}>  $albums
 * @param  array<string, list<string>>  $photos
 */
function fakeFlickr(array $albums, array $photos): void
{
    $isFaked = app()->bound('flickr.fake');
    app()->instance('flickr.fake', ['albums' => $albums, 'photos' => $photos]);

    if (! $isFaked) {
        Http::fake(fn (Request $request) => Http::response(flickrAnswer($request, app('flickr.fake'))));
    }
}

/**
 * @param  array{albums: array<string, array{0: string, 1: int, 2?: string}>, photos: array<string, list<string>>}  $flickr
 * @return array<string, mixed>
 */
function flickrAnswer(Request $request, array $flickr): array
{
    $describe = fn (string $id): array => [
        'id' => $id,
        'title' => ['_content' => $flickr['albums'][$id][0]],
        'description' => ['_content' => $flickr['albums'][$id][2] ?? ''],
        'count_photos' => count($flickr['photos'][$id] ?? []),
        'date_update' => (string) $flickr['albums'][$id][1],
    ];

    return match ($request['method']) {
        'flickr.photosets.getList' => ['stat' => 'ok', 'photosets' => ['photoset' => array_map($describe, array_map('strval', array_keys($flickr['albums'])))]],
        'flickr.photosets.getInfo' => ['stat' => 'ok', 'photoset' => $describe($request['photoset_id'])],
        'flickr.photosets.getPhotos' => ['stat' => 'ok', 'photoset' => ['photo' => array_map(
            fn (string $photo): array => ['id' => $photo, 'secret' => 'abc123', 'server' => '65535'],
            $flickr['photos'][$request['photoset_id']] ?? [],
        )]],
    };
}

function albumPage(string $albumId): Page
{
    return lynx()->pages->findOne("template=flickr-album, flickr_album_id={$albumId}, include=all")
        ?? throw new RuntimeException("No page for album {$albumId}.");
}

function defaultLanguage(): string
{
    return (string) lynx()->languages->default()->code;
}

function getPhotosCalls(): int
{
    return Http::recorded(fn (Request $request): bool => $request['method'] === 'flickr.photosets.getPhotos')->count();
}

it('installs again without changing anything', function (): void {
    $this->artisan('flickr:install')->assertSuccessful();

    expect(lynx()->pages->find('template=flickr-albums, include=all')->count())->toBe(1);
});

it('creates an unpublished page for each new album', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000, 'Two weeks by the sea.'], '222' => ['Winter trip', 1000]], ['111' => ['1', '2'], '222' => ['3']]);

    $this->artisan('flickr:sync')->assertSuccessful();

    $summer = albumPage('111');
    expect((string) $summer->title)->toBe('Summer trip')
        ->and($summer->name())->toBe('summer-trip')
        ->and($summer->parent()?->name())->toBe('photos')
        ->and($summer->hasStatus(PageStatus::UNPUBLISHED))->toBeTrue()
        ->and((string) $summer->flickr_thumbnails)->toBe("https://live.staticflickr.com/65535/1_abc123_q.jpg\nhttps://live.staticflickr.com/65535/2_abc123_q.jpg")
        ->and($summer->getLanguageValue(defaultLanguage(), 'flickr_description'))->toBe('Two weeks by the sea.')
        ->and($summer->getLanguageValue(defaultLanguage(), 'flickr_photo_count'))->toBe(2)
        ->and((string) albumPage('222')->flickr_thumbnails)->toEndWith('3_abc123_q.jpg');
});

it('asks Flickr once per album, because the save hook sees the thumbnails are there', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000], '222' => ['Winter trip', 1000]], ['111' => ['1'], '222' => ['3']]);

    $this->artisan('flickr:sync')->assertSuccessful();

    expect(getPhotosCalls())->toBe(2);
});

it('changes nothing when nothing changed on Flickr', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    $this->artisan('flickr:sync')->assertSuccessful();
    $revision = albumPage('111')->revision;

    $this->artisan('flickr:sync')->assertSuccessful();

    expect(getPhotosCalls())->toBe(1)
        ->and(albumPage('111')->revision)->toBe($revision);
});

it('updates a changed album and keeps what editors wrote', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    $this->artisan('flickr:sync')->assertSuccessful();
    lynx()->run(SavePage::class, ['id' => albumPage('111')->id, 'values' => ['title' => [defaultLanguage() => 'Our summer']]]);

    fakeFlickr(['111' => ['Summer trip', 2000, 'Two weeks by the sea.']], ['111' => ['1', '2']]);
    $this->artisan('flickr:sync')->assertSuccessful();

    expect((string) albumPage('111')->flickr_thumbnails)->toEndWith('2_abc123_q.jpg')
        ->and(albumPage('111')->getLanguageValue(defaultLanguage(), 'flickr_photo_count'))->toBe(2)
        ->and(albumPage('111')->getLanguageValue(defaultLanguage(), 'flickr_description'))->toBe('Two weeks by the sea.')
        ->and((string) albumPage('111')->title)->toBe('Our summer')
        ->and((int) (string) albumPage('111')->flickr_updated_at)->toBe(2000);
});

it('updates the draft too, so publishing it keeps the new thumbnails', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    $this->artisan('flickr:sync')->assertSuccessful();
    lynx()->pages->saveDraft(albumPage('111'), ['title' => [defaultLanguage() => 'Summer trip, edited']]);

    fakeFlickr(['111' => ['Summer trip', 2000]], ['111' => ['1', '2']]);
    $this->artisan('flickr:sync')->assertSuccessful();
    lynx()->pages->publishDraft(albumPage('111'));

    expect((string) albumPage('111')->flickr_thumbnails)->toEndWith('2_abc123_q.jpg')
        ->and((string) albumPage('111')->title)->toBe('Summer trip, edited');
});

it('leaves a page someone is editing for the next sync', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    $this->artisan('flickr:sync')->assertSuccessful();
    lynx()->run(LockEdit::class, ['page' => albumPage('111')->id]);

    fakeFlickr(['111' => ['Summer trip', 2000]], ['111' => ['1', '2']]);
    $plan = app(AlbumSync::class)->plan();

    expect($plan->skipped)->toBe([albumPage('111')->id])
        ->and($plan->updates)->toBe([]);
});

it('writes nothing on a dry run', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);

    lynx()->run(SyncAlbums::class, app(AlbumSync::class)->plan()->toInput(), dryRun: true);

    expect(lynx()->pages->find('template=flickr-album, include=all')->count())->toBe(0);
});

it('refuses people without the flickr.sync permission', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    $input = app(AlbumSync::class)->plan()->toInput();
    lynx()->run(AssignRoles::class, ['user' => 1, 'roles' => ['editor']]);
    $this->actingAs(new GenericUser(['id' => 1]));

    expect(fn () => lynx()->run(SyncAlbums::class, $input))->toThrow(AuthorizationException::class, 'flickr.sync');
});

it('syncs from the button on the albums page', function (): void {
    fakeFlickr(['111' => ['Summer trip', 1000]], ['111' => ['1']]);
    lynx()->run(AssignRoles::class, ['user' => 1, 'roles' => ['superuser']]);
    $this->actingAs(new GenericUser(['id' => 1]));

    Livewire::test(SyncButton::class, ['pageId' => (int) lynx()->pages->get('/photos')?->id])
        ->call('sync')
        ->assertDispatched('lynx-notify');

    expect(albumPage('111')->hasStatus(PageStatus::UNPUBLISHED))->toBeTrue();
});

it('fills an album page an editor adds by hand, title included when it has none', function (): void {
    fakeFlickr(['333' => ['Autumn trip', 3000, 'Leaves.']], ['333' => ['7', '8', '9']]);
    $page = lynx()->pages->new('flickr-album', '/photos');
    $page->name = 'autumn';
    $page->setLanguageValue(defaultLanguage(), 'flickr_album_id', '333');

    $page = lynx()->pages->save($page);

    expect($page->getLanguageValue(defaultLanguage(), 'title'))->toBe('Autumn trip')
        ->and($page->getLanguageValue(defaultLanguage(), 'flickr_description'))->toBe('Leaves.')
        ->and($page->getLanguageValue(defaultLanguage(), 'flickr_photo_count'))->toBe(3)
        ->and($page->getLanguageValue(defaultLanguage(), 'flickr_updated_at'))->toBe(3000)
        ->and((string) $page->getLanguageValue(defaultLanguage(), 'flickr_thumbnails'))->toEndWith('9_abc123_q.jpg');
});
```

Run them from the package's folder:

```bash
cd packages/lynx-flickr
composer install
composer test
```

## 12. Sharing it with other sites

1. Move `packages/lynx-flickr` to a repository of its own and tag a release: `git tag v1.0.0`.
2. Publish it on [Packagist](https://packagist.org), or keep it private and add the repository to each site's `composer.json`.
3. On each site:

```bash
composer require acme/lynx-flickr
php artisan flickr:install
```

Add `FLICKR_API_KEY` and `FLICKR_USER_ID` to the site's `.env`, and the `flickr:sync` line to its `routes/console.php`.

If a site keeps its schema in Git, run `php artisan lynx:schema:export` after `flickr:install` and commit the new files.

## What you have now

- **Editors** press one button to bring new albums into the site. They review each new page before publishing it, and the photos of every album stay in step with Flickr.
- **Each site** gets the feature with two commands and two lines in `.env`.
- **You** keep one copy of the code, with tests that run without a site and without the network.

## Notes for the Lynx documentation

Before this recipe moves into the official docs:

- `SavePage` and `SaveDraft` have no `@api` marker, although the docs tell packages to hook and run them. Mark them, or the recipe depends on code Lynx may change without notice.
- `Page::dirtyValues()` is `@internal`. The hook would be simpler if a package could ask which fields a `$page->save()` changes.
- The official docs test their code in `tests/Feature/Docs`. These recipes would need their snippets moved to `docs/snippets` and run there.
