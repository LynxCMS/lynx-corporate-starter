<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Downloads\DownloadListing;
use App\Stats\PageStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use LynxCms\Core\Pages\Page;

/**
 * The resources list for a static export, in one language: every sort's order of ids and every
 * download's card, rendered by the same card component as the list. A static host ignores ?sort=
 * and ?page=, so resources/js/listing.js fetches this file and shows the sort and page asked for.
 * The export writes it through lynx-static.paths. Links in the cards start from the site root, as
 * the export rewrites them in pages, so one file works on any host.
 */
final class DownloadCardsController
{
    /** As long as DownloadListing keeps its orders, so the file and the pages agree. */
    private const int CACHE_SECONDS = 900;

    public function __invoke(string $language, DownloadListing $listing, PageStats $stats): JsonResponse
    {
        $found = lynx()->languages->find($language);
        abort_if($found === null, 404);
        lynx()->languages->setCurrent($found);

        return response()->json(Cache::remember(
            "downloads:cards:{$language}",
            self::CACHE_SECONDS,
            fn (): array => $this->build($listing, $stats),
        ));
    }

    /** @return array{per_page: int, orders: array<string, list<int>>, cards: object} */
    private function build(DownloadListing $listing, PageStats $stats): array
    {
        $orders = $listing->orders();
        $pages = $listing->pages($orders[DownloadListing::DEFAULT_SORT]);
        $totals = $stats->totals(array_map(fn (Page $page): int => (int) $page->id, $pages), PageStats::DOWNLOAD);
        $cards = [];

        foreach ($pages as $page) {
            $cards[(int) $page->id] = $this->fromRoot(Blade::render(
                '<x-card :page="$page" :downloads="$downloads" />',
                ['page' => $page, 'downloads' => $totals[(int) $page->id] ?? 0],
            ));
        }

        // An object, so the ids stay keys in JSON.
        return ['per_page' => DownloadListing::PER_PAGE, 'orders' => $orders, 'cards' => (object) $cards];
    }

    /** The site's own addresses from its root: https://site/pt/x becomes /pt/x. */
    private function fromRoot(string $html): string
    {
        return str_replace(rtrim(url('/'), '/').'/', '/', $html);
    }
}
