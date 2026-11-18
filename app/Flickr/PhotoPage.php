<?php

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
