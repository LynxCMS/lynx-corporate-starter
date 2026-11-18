<?php

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
