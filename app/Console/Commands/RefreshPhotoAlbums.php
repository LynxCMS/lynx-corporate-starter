<?php

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
