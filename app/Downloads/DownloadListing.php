<?php

declare(strict_types=1);

namespace App\Downloads;

use App\Stats\PageStats;
use App\Stats\Ranking;
use Illuminate\Support\Facades\Cache;
use LynxCms\Core\Pages\Page;

/**
 * The /resources/ list, in pages of 24: Popular (downloads lately, newest first among the rest),
 * Newest, or A to Z in the visitor's language. Each order's ids are kept for 15 minutes, as long as
 * the page cache keeps the list, so a sort and its pages agree.
 */
final readonly class DownloadListing
{
    public const array SORTS = ['popular', 'newest', 'title'];

    public const string DEFAULT_SORT = 'popular';

    public const int PER_PAGE = 24;

    private const int CACHE_SECONDS = 900;

    public function __construct(private Ranking $ranking) {}

    /**
     * One page of the list, or null when the sort is unknown or the page is past the last.
     *
     * @return array{items: list<Page>, page: int, last: int, sort: string}|null
     */
    public function for(string $sort, int $page): ?array
    {
        if (! in_array($sort, self::SORTS, true)) {
            return null;
        }

        $ids = $this->ids($sort);
        $last = max(1, (int) ceil(count($ids) / self::PER_PAGE));

        if ($page < 1 || $page > $last) {
            return null;
        }

        return ['items' => $this->load(array_slice($ids, ($page - 1) * self::PER_PAGE, self::PER_PAGE)), 'page' => $page, 'last' => $last, 'sort' => $sort];
    }

    /** @return list<int> */
    private function ids(string $sort): array
    {
        $language = (string) lynx()->languages->current()->code;

        /** @var list<int> */
        return Cache::remember("downloads:ids:{$sort}:{$language}", self::CACHE_SECONDS, fn (): array => match ($sort) {
            'title' => $this->find('sort=title'),
            'newest' => $this->find('sort=-published_on'),
            default => $this->ranking->ids($this->find('sort=-published_on'), PageStats::DOWNLOAD),
        });
    }

    /** @return list<int> */
    private function find(string $sort): array
    {
        return lynx()->pages->find("template=download, {$sort}")->ids();
    }

    /**
     * @param  list<int>  $ids
     * @return list<Page>
     */
    private function load(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $found = [];

        foreach (lynx()->pages->find('id='.implode('|', $ids)) as $page) {
            $found[(int) $page->id] = $page;
        }

        return array_values(array_filter(array_map(fn (int $id): ?Page => $found[$id] ?? null, $ids)));
    }
}
