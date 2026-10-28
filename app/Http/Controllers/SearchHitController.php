<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Search\Popularity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use LynxCms\Core\Pages\Page;

/**
 * A visitor opened a search result (navigator.sendBeacon from resources/js/search.js). It counts
 * once per address and page a day, never for bots, and only for a published page with a URL.
 * The answer is always empty: the beacon reads nothing, and a refusal tells a scraper nothing.
 */
final class SearchHitController
{
    private const int SECONDS_PER_DAY = 86400;

    private const string BOTS = '/bot|crawl|spider|slurp|preview|monitor|headless/i';

    public function __invoke(Request $request, Popularity $popularity): Response
    {
        $id = filter_var($request->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = is_int($id) && ! preg_match(self::BOTS, (string) $request->userAgent()) ? lynx()->pages->findOne("id={$id}") : null;

        if ($page instanceof Page && $page->template()->hasUrl() && RateLimiter::attempt("search-hit:{$request->ip()}:{$id}", 1, fn (): bool => true, self::SECONDS_PER_DAY)) {
            $popularity->record($id);
        }

        return response()->noContent();
    }
}
