<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Bookmarkable;
use App\Support\SiteLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use LynxCms\Core\Pages\Page;

/**
 * My bookmarks on a static export, in one language: the card of every page a visitor can
 * bookmark, as BookmarkCardsController renders it. A static host has no /bookmarks/cards to ask,
 * so resources/js/bookmarks.js reads this file instead and picks the saved pages from it. The
 * export writes it through lynx-static.paths.
 */
final class BookmarkCardsFileController
{
    private const int CACHE_SECONDS = 900;

    public function __invoke(string $language): JsonResponse
    {
        $found = lynx()->languages->find($language);
        abort_if($found === null, 404);
        lynx()->languages->setCurrent($found);

        return response()->json(Cache::remember("bookmarks:cards:{$language}", self::CACHE_SECONDS, fn (): array => ['cards' => (object) $this->cards()]));
    }

    /** @return array<int, string> page id => card */
    private function cards(): array
    {
        $selector = Bookmarkable::selector();
        $cards = [];

        if ($selector === null) {
            return [];
        }

        foreach (lynx()->pages->find($selector)->publishedIn()->all() as $page) {
            $cards[(int) $page->id] = SiteLinks::fromRoot(view('bookmarks.cards', ['pages' => [$page]])->render());
        }

        return $cards;
    }
}
