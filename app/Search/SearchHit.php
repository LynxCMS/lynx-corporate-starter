<?php

declare(strict_types=1);

namespace App\Search;

use LynxCms\Core\Pages\Page;

/**
 * One search result: a page with a URL, or a page without one, such as a promo, with the page that
 * shows it. Either way it opens a page with a URL, and that page is the one the visit counts for.
 */
final readonly class SearchHit
{
    public function __construct(
        public Page $page,
        public ?Page $shownOn = null,
    ) {}

    /** The page the result opens. */
    public function target(): Page
    {
        return $this->shownOn ?? $this->page;
    }

    public function url(): string
    {
        return $this->target()->url();
    }
}
