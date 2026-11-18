# Photo Albums from Flickr

This recipe adds a **Photo album** page to the site. An editor types the id of a Flickr album and saves. The site then copies from Flickr:

- the album's **thumbnails**, 150 pixel squares, each linking to the photo's page on flickr.com;
- the **number of photos**;
- the album's **description**, which editors can translate;
- the album's **title**, when the page has none yet. A title the editor wrote is never replaced.

The page stays up to date: every save asks Flickr again, and a command runs every night and updates the pages whose album changed on Flickr.

You need:

- this site, with Lynx installed;
- a Flickr API key, which you get at [flickr.com/services/apps/create](https://www.flickr.com/services/apps/create/);
- the id of the Flickr account that owns the albums. It looks like `12345678@N00`.

Every file below is named with the path where it is saved, relative to the site's root folder, and the same path is the first comment in its code.

## 1. Configuring the Flickr key

Add the key and the account id to `.env`:

```ini
# .env
FLICKR_API_KEY=your-key-here
FLICKR_USER_ID=12345678@N00
```

Then give them a place in `config/services.php`, inside the array the file returns. Laravel keeps the keys of outside services there:

```php
// config/services.php (excerpt)
    'flickr' => [
        'key' => env('FLICKR_API_KEY'),
        'user_id' => env('FLICKR_USER_ID'),
    ],
```

## 2. Adding the fields and the template

A *template* lists the fields a kind of page has. A Photo album page has the site's `title` field and four fields of its own:

| Field | Type | Label | Holds |
| --- | --- | --- | --- |
| `flickr_album_id` | text | Flickr album id | The id the editor types. |
| `flickr_description` | textarea, translatable | Album description | Flickr's description in the default language, and the editors' translations. |
| `photo_count` | integer | Photos | The number of photos, from Flickr. |
| `thumbnails` | textarea | Thumbnails | One image address per line, from Flickr. |

The template is `photo`, labeled "Photo album", with the fields `title`, `flickr_album_id`, `flickr_description`, `photo_count` and `thumbnails`.

A *translatable* field holds one value per language. The others are *shared*: one value for every language, because an album has the same photos and the same id in English and in Portuguese.

You can create them in three ways. Each one runs the same Lynx actions, so the result is the same. Pick one.

### In the admin

If the site has the admin (`lynxcms/admin`), sign in as a superuser at `/lynx`:

1. Open **Settings › Templates** and press **New template**. Name it `photo`, label it `Photo album`, and press **Create template**.
2. On the template's screen, check that **Fields** lists `title`. If it doesn't, add it with **Add a field**.
3. Press **Add a field** and create a new field: name `flickr_album_id`, **What it holds** set to **Single line**, label `Flickr album id`. Leave **Translatable** off and press **Create and add**.
4. Do the same for:
   - `flickr_description`: **Paragraphs**, label `Album description`, with **Translatable** on;
   - `photo_count`: **Whole number**, label `Photos`;
   - `thumbnails`: **Paragraphs**, label `Thumbnails`.

### In Tinker

Tinker is Laravel's interactive PHP shell. Start it from the site's folder:

```bash
php artisan tinker
```

Then paste these lines:

```php
// php artisan tinker
lynx()->fields->add('flickr_album_id', 'text', label: 'Flickr album id');
lynx()->fields->add('flickr_description', 'textarea', translatable: true, label: 'Album description');
lynx()->fields->add('photo_count', 'integer', label: 'Photos');
lynx()->fields->add('thumbnails', 'textarea', label: 'Thumbnails');
lynx()->templates->add('photo', fields: ['title', 'flickr_album_id', 'flickr_description', 'photo_count', 'thumbnails'], label: 'Photo album');
```

### In a seeder

A seeder is a Laravel class that fills the database. Choose it when the same schema must reach other copies of the site, such as a colleague's machine or a fresh install. This one adds only what is missing, so it is safe to run again, and it also brings an older `photo` template up to date:

```php
<?php

// database/seeders/PhotoSchemaSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * A photo album page: a Flickr album id that editors type, and what the site copies from the
 * album on every save (App\Flickr\FillFromFlickr). Safe to run again: it adds only what is missing.
 */
final class PhotoSchemaSeeder extends Seeder
{
    /** @var array<string, array{type: string, label: string, translatable: bool}> */
    private const array FIELDS = [
        'flickr_album_id' => ['type' => 'text', 'label' => 'Flickr album id', 'translatable' => false],
        'flickr_description' => ['type' => 'textarea', 'label' => 'Album description', 'translatable' => true],
        'photo_count' => ['type' => 'integer', 'label' => 'Photos', 'translatable' => false],
        'thumbnails' => ['type' => 'textarea', 'label' => 'Thumbnails', 'translatable' => false],
    ];

    public function run(): void
    {
        foreach (self::FIELDS as $name => $field) {
            if (lynx()->fields->find($name) === null) {
                lynx()->fields->add($name, $field['type'], translatable: $field['translatable'], label: $field['label']);
            }
        }

        $fields = ['title', ...array_keys(self::FIELDS)];

        lynx()->templates->find('photo') === null
            ? lynx()->templates->add('photo', fields: $fields, label: 'Photo album')
            : lynx()->templates->update('photo', ['add' => $fields]);
    }
}
```

Run it:

```bash
php artisan db:seed --class=PhotoSchemaSeeder
```

### After creating them

Whichever way you chose:

- If you run PHPStan, regenerate the field types it reads, so it knows `$page->flickr_album_id` and the other new fields: `php artisan lynx:phpstan`.
- If you keep the schema in Git, export it and commit the files: `php artisan lynx:schema:export`. Other copies of the site then apply it with `php artisan lynx:schema:apply`.

## 3. Reading an album from Flickr

An album, as a photo page shows it:

```php
<?php

// app/Flickr/Album.php

declare(strict_types=1);

namespace App\Flickr;

/** A Flickr album as a photo page shows it. */
final readonly class Album
{
    /**
     * @param  list<string>  $thumbnails  addresses, in the album's order
     */
    public function __construct(
        public string $title,
        public string $description,
        public int $photoCount,
        public array $thumbnails,
    ) {}

    /** The thumbnails as the page keeps them: one address per line. */
    public function thumbnailLines(): string
    {
        return implode("\n", $this->thumbnails);
    }
}
```

The client asks Flickr twice: `flickr.photosets.getInfo` for the title, the description and the number of photos, then `flickr.photosets.getPhotos` for the photos themselves.

```php
<?php

// app/Flickr/FlickrAlbum.php

declare(strict_types=1);

namespace App\Flickr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/** Reads one album from Flickr's REST API: its details, then its photos. */
final readonly class FlickrAlbum
{
    private const string ENDPOINT = 'https://www.flickr.com/services/rest/';

    private const int TIMEOUT_SECONDS = 5;

    /** Flickr's largest page. Albums with more photos show their first 500. */
    private const int MAX_PHOTOS = 500;

    /**
     * Flickr's image addresses, from its photo source URL format. Size "q" is a 150 pixel square.
     * https://www.flickr.com/services/api/misc.urls.html
     */
    private const string IMAGE_URL = 'https://live.staticflickr.com/%s/%s_%s_q.jpg';

    /**
     * @throws AlbumNotFound when Flickr has no such album
     * @throws ConnectionException|RequestException when Flickr cannot be reached or fails
     */
    public function find(string $albumId): Album
    {
        $info = $this->call('flickr.photosets.getInfo', $albumId, []);
        $photos = $this->call('flickr.photosets.getPhotos', $albumId, ['per_page' => self::MAX_PHOTOS]);

        return new Album(
            title: $this->text($info, 'photoset.title._content'),
            // Flickr allows a few HTML tags in descriptions; the page shows plain text.
            description: trim(strip_tags($this->text($info, 'photoset.description._content'))),
            photoCount: (int) data_get($info, 'photoset.count_photos', 0),
            thumbnails: $this->thumbnails($photos),
        );
    }

    /**
     * @param  array<string, string|int>  $parameters
     * @return array<string, mixed>
     *
     * @throws AlbumNotFound|ConnectionException|RequestException
     */
    private function call(string $method, string $albumId, array $parameters): array
    {
        $response = Http::timeout(self::TIMEOUT_SECONDS)->get(self::ENDPOINT, [
            'method' => $method,
            'api_key' => config('services.flickr.key'),
            'user_id' => config('services.flickr.user_id'),
            'photoset_id' => $albumId,
            'format' => 'json',
            'nojsoncallback' => 1,
            ...$parameters,
        ])->throw();

        if ($response->json('stat') !== 'ok') {
            throw AlbumNotFound::withId($albumId);
        }

        /** @var array<string, mixed> */
        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $photos
     * @return list<string>
     */
    private function thumbnails(array $photos): array
    {
        /** @var list<array<string, mixed>> $list */
        $list = data_get($photos, 'photoset.photo', []);

        return array_map(fn (array $photo): string => sprintf(
            self::IMAGE_URL,
            $this->text($photo, 'server'),
            $this->text($photo, 'id'),
            $this->text($photo, 'secret'),
        ), $list);
    }

    /** @param  array<string, mixed>  $data */
    private function text(array $data, string $key): string
    {
        $value = data_get($data, $key);

        return is_string($value) ? $value : '';
    }
}
```

Each photo in Flickr's answer carries a `server`, an `id` and a `secret`. Flickr builds every image address from these three and a size letter, as its [photo source URL](https://www.flickr.com/services/api/misc.urls.html) page explains. The letter `q` is a 150 pixel square; `b`, for example, is 1024 pixels on the longest side.

Flickr allows a few HTML tags in descriptions, so the client keeps the text only. The photo count leaves out videos.

Flickr answers an unknown album id with `"stat": "fail"`, so the client throws its own exception for it:

```php
<?php

// app/Flickr/AlbumNotFound.php

declare(strict_types=1);

namespace App\Flickr;

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

`->throw()` turns a server error into a `RequestException`, and a timeout becomes a `ConnectionException`. The next step decides what each of them means for the editor.

## 4. Filling the page on every save

In Lynx, every change is an *action*: saving a page runs the `pages.save` action, whoever saves it (an editor in the admin, your code, an import or an AI agent). A *before hook* is a function that runs before an action and may change its input. This one adds Flickr's values to the save:

```php
<?php

// app/Flickr/FillFromFlickr.php

declare(strict_types=1);

namespace App\Flickr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Runner\Input;
use LynxCms\Core\Pages\Page;
use Stringable;

/**
 * A before hook on pages.save: when a photo page is saved, it asks Flickr for the album and adds
 * to the same save its thumbnails, its photo count and its description (in the default language;
 * editors translate it). The album's title goes in only when the page has no title yet.
 *
 * pages.save receives a page in two shapes: the Page itself (from $page->save()), or an id and
 * the values that changed (from the admin, the API, MCP clients and drafts).
 */
final readonly class FillFromFlickr
{
    private const string TEMPLATE = 'photo';

    public function __construct(private FlickrAlbum $flickr) {}

    public function __invoke(Input $input): Input
    {
        $page = $input->get('page');

        return $page instanceof Page ? $this->fillPage($page, $input) : $this->fillValues($input);
    }

    /** $page->save(): the hook sets the fields on the page itself. */
    private function fillPage(Page $page, Input $input): Input
    {
        if ($page->template()->name !== self::TEMPLATE) {
            return $input;
        }

        $album = $this->fetch($this->albumId($page->flickr_album_id), $page->id);

        if ($album === null) {
            return $input;
        }

        $language = (string) lynx()->languages->default()->code;
        $page->thumbnails = $album->thumbnailLines();
        $page->photo_count = $album->photoCount;
        $page->setLanguageValue($language, 'flickr_description', $album->description);

        if ($this->isBlank($page->getLanguageValue($language, 'title'))) {
            $page->setLanguageValue($language, 'title', $album->title);
        }

        return $input;
    }

    /** An id and the changed values: the hook adds Flickr's values to them. */
    private function fillValues(Input $input): Input
    {
        $id = $input->int('id');
        $stored = $id === null ? null : lynx()->pages->get($id);
        $template = $stored?->template()->name ?? $input->string('template');

        // A save that brings its own thumbnails, such as a hand edit or the nightly refresh, is left as it is.
        if ($template !== self::TEMPLATE || $input->has('values.thumbnails')) {
            return $input;
        }

        // The admin sends only the fields that changed, so the album id may be the stored one.
        $albumId = $input->has('values.flickr_album_id') ? $input->get('values.flickr_album_id') : $stored?->flickr_album_id;
        $album = $this->fetch($this->albumId($albumId), $id);

        return $album === null ? $input : $this->withAlbum($input, $album, $stored);
    }

    private function withAlbum(Input $input, Album $album, ?Page $stored): Input
    {
        $language = (string) lynx()->languages->default()->code;
        $title = $input->has("values.title.{$language}") ? $input->get("values.title.{$language}") : $stored?->getLanguageValue($language, 'title');

        $input = $input->with('values.thumbnails', $album->thumbnailLines())
            ->with('values.photo_count', $album->photoCount)
            ->with("values.flickr_description.{$language}", $album->description);

        return $this->isBlank($title) ? $input->with("values.title.{$language}", $album->title) : $input;
    }

    /** The album, or null when there is nothing to change. */
    private function fetch(?string $albumId, ?int $pageId): ?Album
    {
        if ($albumId === null) {
            return null;
        }

        try {
            return $this->flickr->find($albumId);
        } catch (AlbumNotFound $notFound) {
            // Keyed by the field, the page editor shows the message under it, and nothing is saved.
            throw ValidationException::withMessages(['values.flickr_album_id' => $notFound->getMessage()]);
        } catch (ConnectionException|RequestException $failure) {
            // Editors can still save while Flickr is down. The album's values stay as they were.
            Log::warning('Flickr could not be reached; the album was not refreshed.', [
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

What it writes:

- **Thumbnails and photo count**, on every save.
- **The description in the default language**, on every save. Editors translate it into the other languages, and Flickr never touches those.
- **The title**, only when the page has no title in the default language.

When it writes nothing:

- **Another template, or no album id.** The save goes on unchanged, and Flickr is not asked.
- **The save brings its own thumbnails**, for example an editor who edited the list by hand. The hook leaves the save as it is. Step 7 uses this too.
- **Flickr does not know the album.** The hook throws a validation error keyed by the album field. The page editor shows the message under that field, and nothing is saved.
- **Flickr cannot be reached.** The hook logs a warning and the save goes on with the old values, so an outage at Flickr never stops editors.

`pages.save` receives a page in two shapes. `$page->save()` in your code passes the page itself, and the hook sets the fields on it. The admin, the API and drafts pass the page's id and only the values that changed, so the hook reads the album id and the title from the stored page when the save leaves them out.

Register the hook in `app/Providers/AppServiceProvider.php`:

```php
// app/Providers/AppServiceProvider.php (excerpt)
use App\Flickr\FillFromFlickr;
use LynxCms\Core\Actions\Pages\SavePage;

public function boot(): void
{
    // Photo pages take their album's thumbnails, photo count and description from Flickr on every save, whoever saves them.
    lynx()->hook(SavePage::class)->before($this->app->make(FillFromFlickr::class)(...));
}
```

Flickr's values are written in the same save as the album id. Lynx keeps one revision for it, and the editor sees them as soon as the save is done.

A hook runs inside the save's database transaction, so each Flickr request has a 5 second timeout. A listener for the `PageSaved` event would wait until the save is done and save the page a second time. An editor who still had the page open would then have their next save refused, because Lynx refuses a save made on an older revision of the page. The hook avoids that.

## 5. Trying it in the admin

1. Open the admin at `/lynx` and add a page under the home page. Choose the **Photo album** template.
2. Paste an album id into **Flickr album id**, and save. Leave the title empty to get Flickr's.
3. **Album description**, **Photos** and **Thumbnails** now hold Flickr's values.
4. Switch to the Portuguese tab and translate the description. The next save keeps your translation.
5. Type an id that does not exist and save. The message "Flickr has no album [...]" appears under the field, and the page keeps its last saved values.

Drafts work too. When an editor saves a draft, Flickr's values go into the draft, and they reach the live page when the draft is published.

## 6. Showing the album

Each thumbnail links to the photo's own page on flickr.com, opened inside its album. The photo id is the first part of the image's file name, so a small class builds that address from the thumbnail:

```php
<?php

// app/Flickr/PhotoPage.php

declare(strict_types=1);

namespace App\Flickr;

/**
 * The address of a photo's own page on flickr.com, opened inside its album. The photo id is the
 * first part of an image's file name: 54228000054_5337c468d3_q.jpg.
 */
final class PhotoPage
{
    private const string URL = 'https://www.flickr.com/photos/%s/%s/in/album-%s/';

    private const string PHOTO_ID = '#/(\d+)_[0-9a-f]+(?:_[a-z0-9]+)?\.jpg$#';

    /** Null when the address is not a Flickr image, such as one an editor typed by hand. */
    public static function url(string $image, string $albumId): ?string
    {
        $account = config('services.flickr.user_id');

        if (! is_string($account) || preg_match(self::PHOTO_ID, $image, $match) !== 1) {
            return null;
        }

        return sprintf(self::URL, $account, $match[1], $albumId);
    }
}
```

A link that holds only an image needs a name for screen readers. Add it to the site's language files:

```php
// lang/en/site.php (excerpt)
    'open_on_flickr' => 'Photo :number of :count, on Flickr',
```

```php
// lang/pt/site.php (excerpt)
    'open_on_flickr' => 'Fotografia :number de :count, no Flickr',
```

```php
// lang/es/site.php (excerpt)
    'open_on_flickr' => 'Foto :number de :count, en Flickr',
```

The page's template view prints the description, the number of photos and the thumbnails:

```blade
{{-- resources/views/templates/photo.blade.php --}}
{{-- A photo album: its title, description and photo count, then the Flickr thumbnails that App\Flickr\FillFromFlickr keeps in the page. Each opens the photo's page on flickr.com. --}}
@php
    $thumbnails = array_values(array_filter(explode("\n", (string) $page->thumbnails)));
    $photoCount = (int) (string) $page->photo_count;
    $albumId = (string) $page->flickr_album_id;
@endphp
<x-layout :page="$page">
    <div class="shell py-16">
        <h1 class="text-display font-light">{{ $page->title }}</h1>
        @if ($page->flickr_description)
            <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->flickr_description }}</p>
        @endif
        <p class="mt-4 text-sm text-ink-soft">{{ trans_choice(':count photo|:count photos', $photoCount) }}</p>
        <ul class="mt-10 grid grid-cols-3 gap-4 sm:grid-cols-5">
            @foreach ($thumbnails as $index => $image)
                @php($photoPage = \App\Flickr\PhotoPage::url($image, $albumId))
                <li>
                    @if ($photoPage)
                        <a href="{{ $photoPage }}" target="_blank" rel="noopener" class="block focus-visible:outline-2 focus-visible:outline-offset-2"
                           aria-label="{{ __('site.open_on_flickr', ['number' => $index + 1, 'count' => count($thumbnails)]) }}">
                            <img src="{{ $image }}" alt="" width="150" height="150" loading="lazy" class="w-full rounded">
                        </a>
                    @else
                        <img src="{{ $image }}" alt="" width="150" height="150" loading="lazy" class="w-full rounded">
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</x-layout>
```

- `loading="lazy"` lets the browser fetch the images as the visitor scrolls to them, and `width` and `height` keep the grid from jumping while they load.
- `rel="noopener"` stops flickr.com, opened in a new tab, from reaching back into the site's tab.
- A line an editor typed by hand that is not a Flickr image shows without a link.

## 7. Refreshing changed albums every night

A save refreshes the page, but photos added on Flickr would wait for the next save. This command checks every photo page and saves only the pages whose values changed:

```php
<?php

// app/Console/Commands/RefreshPhotoAlbums.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Flickr\Album;
use App\Flickr\AlbumNotFound;
use App\Flickr\FlickrAlbum;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use LynxCms\Core\Actions\Pages\SavePage;
use LynxCms\Core\Pages\Page;

/**
 * Brings every photo page up to date with its Flickr album: thumbnails, photo count and
 * description. A page is saved only when something changed, and a page with a draft gets the new
 * values in the draft too, because publishing a draft writes all of the draft's values back to
 * the page. Titles are left alone: editors own them once the page has one.
 */
#[Signature('photos:refresh')]
#[Description('Refresh the photo pages whose Flickr album changed')]
final class RefreshPhotoAlbums extends Command
{
    public function handle(FlickrAlbum $flickr): int
    {
        $language = (string) lynx()->languages->default()->code;
        $updated = 0;

        foreach (lynx()->pages->find('template=photo, flickr_album_id!=, include=all') as $page) {
            $album = $this->album($flickr, $page);

            if ($album === null || $this->isUpToDate($page, $album, $language)) {
                continue;
            }

            // Passing the thumbnails tells the save hook not to ask Flickr again.
            $values = ['thumbnails' => $album->thumbnailLines(), 'photo_count' => $album->photoCount, 'flickr_description' => [$language => $album->description]];
            lynx()->run(SavePage::class, ['id' => $page->id, 'values' => $values]);

            if (lynx()->pages->draft($page) !== null) {
                lynx()->pages->saveDraft($page, $values);
            }

            $updated++;
        }

        $this->info("Updated {$updated} photo page(s).");

        return self::SUCCESS;
    }

    /** The page's album, or null when Flickr could not tell. */
    private function album(FlickrAlbum $flickr, Page $page): ?Album
    {
        $albumId = (string) $page->flickr_album_id;

        try {
            return $flickr->find($albumId);
        } catch (AlbumNotFound|ConnectionException|RequestException $failure) {
            Log::warning('Skipped refreshing a photo page.', ['page' => $page->id, 'album' => $albumId, 'error' => $failure->getMessage()]);

            return null;
        }
    }

    private function isUpToDate(Page $page, Album $album, string $language): bool
    {
        return $page->getLanguageValue($language, 'thumbnails') === $album->thumbnailLines()
            && $page->getLanguageValue($language, 'photo_count') === $album->photoCount
            && $page->getLanguageValue($language, 'flickr_description') === $album->description;
    }
}
```

Three details keep it correct:

- **It writes only on a change.** Lynx records every save in the page's history, even one that changes nothing, so the command compares the values first.
- **It updates the draft too.** Publishing a draft writes all of the draft's values back to the page. A draft that still had the old values would bring them back.
- **It leaves the title alone.** Once a page has a title, it belongs to the editors.

The command passes the thumbnails in its save, so the hook from step 4 does not ask Flickr a second time.

Schedule it in `routes/console.php`:

```php
// routes/console.php (excerpt)
use Illuminate\Support\Facades\Schedule;

// Photo pages follow their Flickr albums: new photos and a changed description show up overnight.
Schedule::command('photos:refresh')->dailyAt('03:00');
```

Laravel's scheduler needs its cron entry on the server. You can also run the command by hand: `php artisan photos:refresh`.

The command runs as Artisan, which may save pages that someone has open in the admin. An editor who works at 3 a.m. on an album that changed on Flickr would have to reload the page before saving. The package version of this recipe skips pages that are being edited.

## 8. Testing it

These tests fake Flickr with `Http::fake()`, so they never call the real API. `fakeFlickrAlbums()` can be called again in a test to change what Flickr answers, for example to add a photo before the nightly refresh. They rely on two things this site's `tests/Pest.php` already gives every feature test: the seeded site, and a `home()` helper that returns the home page. They create the schema with `PhotoSchemaSeeder` from step 2, because every test starts from a fresh database. If you created the fields in the admin or in Tinker, add that seeder too: the tests need it.

```php
<?php

// tests/Feature/FlickrPhotoAlbumsTest.php

declare(strict_types=1);

use Database\Seeders\PhotoSchemaSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Actions\Pages\SavePage;
use LynxCms\Core\Pages\Page;

beforeEach(function (): void {
    config(['services.flickr.key' => 'test-key', 'services.flickr.user_id' => '12345678@N00']);
    $this->seed(PhotoSchemaSeeder::class);
});

/**
 * Fakes Flickr with these albums: id => [title, description, photo ids]. An id missing here is
 * one Flickr does not know. Calling it again changes what the fake answers from then on.
 *
 * @param  array<string, array{0: string, 1: string, 2: list<string>}>  $albums
 */
function fakeFlickrAlbums(array $albums): void
{
    $isFaked = app()->bound('flickr.albums');
    app()->instance('flickr.albums', $albums);

    if (! $isFaked) {
        Http::fake(fn (Request $request) => Http::response(flickrAnswer($request, app('flickr.albums'))));
    }
}

/**
 * @param  array<string, array{0: string, 1: string, 2: list<string>}>  $albums
 * @return array<string, mixed>
 */
function flickrAnswer(Request $request, array $albums): array
{
    $album = $albums[$request['photoset_id']] ?? null;

    if ($album === null) {
        return ['stat' => 'fail', 'code' => 1, 'message' => 'Photoset not found'];
    }

    return match ($request['method']) {
        'flickr.photosets.getInfo' => ['stat' => 'ok', 'photoset' => [
            'title' => ['_content' => $album[0]],
            'description' => ['_content' => $album[1]],
            'count_photos' => count($album[2]),
        ]],
        'flickr.photosets.getPhotos' => ['stat' => 'ok', 'photoset' => ['photo' => array_map(
            fn (string $id): array => ['id' => $id, 'secret' => 'abc123', 'server' => '65535'],
            $album[2],
        )]],
    };
}

function photoPage(string $albumId, ?string $title = 'Summer trip'): Page
{
    $page = lynx()->pages->new('photo');
    $page->name = 'summer-trip';
    $page->flickr_album_id = $albumId;

    if ($title !== null) {
        $page->title = $title;
    }

    return lynx()->pages->save($page);
}

function stored(Page $page): Page
{
    return lynx()->pages->get((int) $page->id) ?? throw new RuntimeException('The page is gone.');
}

it('fills the thumbnails, the photo count and the description when a photo page is saved', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', 'Two weeks by the sea.', ['1', '2']]]);

    $page = stored(photoPage('72157'));

    expect((string) $page->thumbnails)->toBe("https://live.staticflickr.com/65535/1_abc123_q.jpg\nhttps://live.staticflickr.com/65535/2_abc123_q.jpg")
        ->and((int) (string) $page->photo_count)->toBe(2)
        ->and($page->getLanguageValue('en', 'flickr_description'))->toBe('Two weeks by the sea.');
});

it('takes the title from Flickr when the page has none', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);

    expect(stored(photoPage('72157', title: null))->getLanguageValue('en', 'title'))->toBe('Summer 2026');
});

it('keeps a title the editor wrote', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);

    expect(stored(photoPage('72157', title: 'Our summer'))->getLanguageValue('en', 'title'))->toBe('Our summer');
});

it('writes the description in the default language and keeps the translations', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', 'Two weeks by the sea.', ['1']]]);
    $page = photoPage('72157');

    lynx()->run(SavePage::class, ['id' => $page->id, 'values' => ['flickr_description' => ['pt' => 'Duas semanas à beira-mar.']]]);

    expect(stored($page)->getLanguageValue('pt', 'flickr_description'))->toBe('Duas semanas à beira-mar.')
        ->and(stored($page)->getLanguageValue('en', 'flickr_description'))->toBe('Two weeks by the sea.');
});

it('refreshes from the stored album id when the admin saves another field', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);
    $page = photoPage('72157');
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1', '3']]]);

    // The admin sends only the fields that changed.
    lynx()->run(SavePage::class, ['id' => $page->id, 'values' => ['title' => ['en' => 'Summer trip 2026']]]);

    expect((string) stored($page)->thumbnails)->toEndWith('3_abc123_q.jpg')
        ->and((int) (string) stored($page)->photo_count)->toBe(2);
});

it('refuses an album Flickr does not know', function (): void {
    fakeFlickrAlbums([]);

    expect(fn () => photoPage('999'))->toThrow(ValidationException::class, 'Flickr has no album [999].');
    expect(lynx()->pages->find('template=photo, include=all')->count())->toBe(0);
});

it('saves without Flickr values when Flickr cannot be reached', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);
    $page = photoPage('72157');
    Http::fake(fn () => throw new ConnectionException('Timed out'));
    Log::spy();

    lynx()->run(SavePage::class, ['id' => $page->id, 'values' => ['title' => ['en' => 'Renamed']]]);

    expect((string) stored($page)->thumbnails)->toEndWith('1_abc123_q.jpg')
        ->and(stored($page)->getLanguageValue('en', 'title'))->toBe('Renamed');
    Log::shouldHaveReceived('warning')->once();
});

it('leaves other templates alone', function (): void {
    Http::fake();

    $about = home();
    $about->title = 'Home, again';
    lynx()->pages->save($about);

    Http::assertNothingSent();
});

it('keeps thumbnails that an editor sets by hand', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);
    $page = photoPage('72157');
    $requests = count(Http::recorded());

    lynx()->run(SavePage::class, ['id' => $page->id, 'values' => ['thumbnails' => 'https://example.com/mine.jpg']]);

    expect((string) stored($page)->thumbnails)->toBe('https://example.com/mine.jpg')
        ->and(count(Http::recorded()))->toBe($requests);
});

it('fills the draft when an editor saves a draft', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']], '72158' => ['Winter 2026', '', ['2']]]);
    $page = photoPage('72157');

    lynx()->pages->saveDraft($page, ['flickr_album_id' => '72158']);

    expect((string) lynx()->pages->draft($page)?->thumbnails)->toEndWith('2_abc123_q.jpg')
        ->and((string) stored($page)->thumbnails)->toEndWith('1_abc123_q.jpg');

    lynx()->pages->publishDraft($page);

    expect((string) stored($page)->thumbnails)->toEndWith('2_abc123_q.jpg');
});

it('refreshes changed albums every night and leaves the others alone', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', 'By the sea.', ['1']]]);
    $page = photoPage('72157');
    fakeFlickrAlbums(['72157' => ['Summer 2026', 'Two weeks by the sea.', ['1', '2']]]);

    $this->artisan('photos:refresh')->assertSuccessful();
    $revision = stored($page)->revision;
    $this->artisan('photos:refresh')->assertSuccessful();

    expect((string) stored($page)->thumbnails)->toEndWith('2_abc123_q.jpg')
        ->and((int) (string) stored($page)->photo_count)->toBe(2)
        ->and(stored($page)->getLanguageValue('en', 'flickr_description'))->toBe('Two weeks by the sea.')
        ->and(stored($page)->revision)->toBe($revision);
});

it('refreshes the draft too, so publishing it keeps the new values', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']]]);
    $page = photoPage('72157');
    lynx()->pages->saveDraft($page, ['title' => ['en' => 'Summer trip, edited']]);
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1', '2']]]);

    $this->artisan('photos:refresh')->assertSuccessful();
    lynx()->pages->publishDraft($page);

    expect((string) stored($page)->thumbnails)->toEndWith('2_abc123_q.jpg')
        ->and(stored($page)->getLanguageValue('en', 'title'))->toBe('Summer trip, edited');
});

it('skips an album Flickr no longer knows and refreshes the others', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', '', ['1']], '72158' => ['Winter 2026', '', ['5']]]);
    photoPage('72157');
    $winter = lynx()->pages->new('photo');
    $winter->name = 'winter-trip';
    $winter->flickr_album_id = '72158';
    $winter = lynx()->pages->save($winter);
    fakeFlickrAlbums(['72158' => ['Winter 2026', '', ['5', '6']]]);

    $this->artisan('photos:refresh')->assertSuccessful();

    expect((string) stored($winter)->thumbnails)->toEndWith('6_abc123_q.jpg');
});

it('shows the album on the page', function (): void {
    fakeFlickrAlbums(['72157' => ['Summer 2026', 'Two weeks by the sea.', ['1', '2']]]);
    $page = photoPage('72157');

    $this->get($page->url())
        ->assertOk()
        ->assertSee('Two weeks by the sea.')
        ->assertSee('2 photos')
        ->assertSee('src="https://live.staticflickr.com/65535/2_abc123_q.jpg"', escape: false)
        ->assertSee('href="https://www.flickr.com/photos/12345678@N00/2/in/album-72157/"', escape: false);
});
```

Run them:

```bash
php artisan test --filter=FlickrPhotoAlbums
```

## What you have now

- **Editors** paste an album id and save. The page fills itself with the album's photos, count and description, a wrong id is caught at once, and the page follows the album on Flickr without anyone touching it. Translations and titles stay theirs.
- **Visitors** see the album as a grid of squares, each opening the photo on Flickr.
- **You** have one hook that works for every way of saving a page, a nightly command, and tests that run without the network.

To use the same feature on several sites, see [Sharing the Flickr Feature as a Package](./flickr-package.md).
