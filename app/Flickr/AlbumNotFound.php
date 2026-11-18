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
