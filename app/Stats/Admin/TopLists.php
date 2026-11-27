<?php

declare(strict_types=1);

namespace App\Stats\Admin;

use App\Stats\PageStats;
use Illuminate\Support\Facades\Cache;
use LynxCms\Core\Pages\Page;

/**
 * The admin home's lists: the most visited pages and the most downloaded files of the last 30 days,
 * each with its count and its share of the first. Pages since deleted are left out. Kept for five
 * minutes, so the home screen stays quick.
 */
final readonly class TopLists
{
    public const int DAYS = 30;

    private const int SHOWN = 10;

    private const int CACHE_SECONDS = 300;

    public function __construct(private PageStats $stats) {}

    /** @return list<array{id: int, title: string, url: string, subject: string, total: int, share: float}> */
    public function pages(): array
    {
        return Cache::remember('stats:top:views', self::CACHE_SECONDS, fn (): array => $this->rows($this->stats->top(PageStats::VIEW, self::DAYS, self::SHOWN * 2)));
    }

    /** @return list<array{id: int, title: string, url: string, subject: string, total: int, share: float}> */
    public function files(): array
    {
        return Cache::remember('stats:top:downloads', self::CACHE_SECONDS, fn (): array => $this->rows($this->stats->top(PageStats::DOWNLOAD, self::DAYS, self::SHOWN * 2, bySubject: true)));
    }

    /**
     * @param  list<array{page_id: int, subject: string, total: int}>  $top
     * @return list<array{id: int, title: string, url: string, subject: string, total: int, share: float}>
     */
    private function rows(array $top): array
    {
        $rows = [];
        $first = $top[0]['total'] ?? 0;

        foreach ($top as $entry) {
            $page = lynx()->pages->get($entry['page_id']);

            if (! $page instanceof Page || count($rows) === self::SHOWN) {
                continue;
            }

            $rows[] = [
                'id' => $entry['page_id'],
                'title' => (string) $page->title,
                'url' => route('lynx.admin.pages.edit', $entry['page_id']),
                'subject' => $entry['subject'],
                'total' => $entry['total'],
                'share' => $first > 0 ? round($entry['total'] / $first * 100, 1) : 0.0,
            ];
        }

        return $rows;
    }
}
