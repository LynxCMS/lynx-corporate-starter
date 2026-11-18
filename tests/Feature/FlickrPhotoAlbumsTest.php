<?php

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
