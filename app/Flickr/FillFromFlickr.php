<?php

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
