<?php

declare(strict_types=1);

namespace App\Search;

use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;

/**
 * Pages without a URL that only show inside other pages, such as promos, found by their words: the
 * result keeps the promo's title and links to a page that shows it, found through its backlinks as
 * the admin lists them. Only pages a visitor can open count, so a promo that no published page
 * shows is not found at all.
 */
final class ShownInside
{
    /** Templates without a URL whose words lead to the pages that show them. */
    private const array TEMPLATES = ['promo'];

    /**
     * The pages found, then what matched inside other pages, up to $limit.
     *
     * @return list<SearchHit>
     */
    public static function add(PageArray $found, string $search, int $limit): array
    {
        $hits = array_map(fn (Page $page): SearchHit => new SearchHit($page), $found->all());

        foreach (lynx()->pages->find('template='.implode('|', self::TEMPLATES).', '.$search.', limit='.$limit) as $inside) {
            $host = self::host($inside);

            if ($host !== null) {
                $hits[] = new SearchHit($inside, $host);
            }
        }

        return array_slice($hits, 0, $limit);
    }

    /** The first page a visitor can open that shows $inside, lowest id first. */
    private static function host(Page $inside): ?Page
    {
        $ids = array_filter(lynx()->pages->backlinks($inside)->map(fn (Page $page): ?int => $page->id));

        // Backlinks include every status; found again by id, they keep only what a visitor sees.
        foreach ($ids === [] ? [] : lynx()->pages->find('id='.implode('|', $ids).', sort=id') as $page) {
            if ($page->template()->hasUrl()) {
                return $page;
            }
        }

        return null;
    }
}
