<?php

declare(strict_types=1);

namespace App\Search;

use Database\Seeders\SectionsSchemaSeeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Pages\PageArray;
use LynxCms\Core\Support\Value;

/**
 * The pages the search panel suggests: the ones editors pick on the Search page, in their order,
 * then the pages most opened from search lately (Popularity), then the newest pages, until the
 * Search page's "How many" is reached. Each page shows once, and only if visitors may see it in
 * their language.
 */
final readonly class Suggestions
{
    public const int DEFAULT_SIZE = 6;

    /** Popular pages read per suggestion, so pages since unpublished still leave enough. */
    private const int CANDIDATES_PER_SLOT = 3;

    public function __construct(private Popularity $popularity) {}

    /** @return list<Page> */
    public function for(Page $search): array
    {
        $size = Value::int($search->getUnformatted('list_size'), self::DEFAULT_SIZE) ?: self::DEFAULT_SIZE;
        $picked = $search->suggested->publishedIn()->all();

        $pages = [];
        $this->add($pages, $picked, $size);
        $this->add($pages, $this->trending($size), $size);
        $this->add($pages, $this->newest($size), $size);

        return array_values($pages);
    }

    /**
     * @param  array<int, Page>  $pages  id => page, in order
     * @param  iterable<Page>  $more
     */
    private function add(array &$pages, iterable $more, int $size): void
    {
        foreach ($more as $page) {
            if (count($pages) >= $size) {
                return;
            }

            $pages[(int) $page->id] ??= $page;
        }
    }

    /** @return list<Page> */
    private function trending(int $size): array
    {
        $ids = $this->popularity->top($size * self::CANDIDATES_PER_SLOT);

        if ($ids === []) {
            return [];
        }

        $found = lynx()->pages->find($this->cards().', id='.implode('|', $ids))->publishedIn()->all();
        $byId = array_column(array_map(fn (Page $page): array => [(int) $page->id, $page], $found), 1, 0);

        return array_values(array_filter(array_map(fn (int $id): ?Page => $byId[$id] ?? null, $ids)));
    }

    private function newest(int $size): PageArray
    {
        return lynx()->pages->find($this->cards().', sort=-published, limit='.($size * 2))->publishedIn();
    }

    /** Pages with a URL that a card can show: promos have none, so they are only ever picked. */
    private function cards(): string
    {
        return 'template='.implode('|', array_diff(SectionsSchemaSeeder::CARD_TEMPLATES, ['promo']));
    }
}
