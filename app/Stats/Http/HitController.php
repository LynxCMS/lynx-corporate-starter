<?php

declare(strict_types=1);

namespace App\Stats\Http;

use App\Stats\PageStats;
use App\Stats\RealVisitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use LynxCms\Core\Pages\Page;

/**
 * The beacon (components/stats-beacon): a view or one of the site's events on a page. Registered
 * outside the web middleware group, so it starts no session and sets no cookie. It counts only
 * same-site requests from real visitors who aren't signed-in editors, on a published page with an
 * address, for events the site declares. The answer is always empty: the beacon reads nothing, and a
 * refusal tells a scraper nothing.
 */
final class HitController
{
    private const int SECONDS_PER_MINUTE = 60;

    public function __invoke(Request $request, PageStats $stats): Response
    {
        $event = (string) $request->input('event', PageStats::VIEW);
        $page = $this->page($request);

        if ($page instanceof Page && $page->id !== null && $this->counts($request, $event)) {
            $stats->record($page->id, $event, subject: (string) $request->input('subject', ''));
        }

        return response()->noContent();
    }

    private function counts(Request $request, string $event): bool
    {
        return $this->isSameSite($request)
            && RealVisitor::is($request)
            && ! $request->cookies->has((string) config('stats.skip_cookie'))
            && in_array($event, [PageStats::VIEW, ...(array) config('stats.events', [])], true)
            && $this->withinLimit($request);
    }

    private function page(Request $request): ?Page
    {
        $id = filter_var($request->input('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = is_int($id) ? lynx()->pages->findOne("id={$id}, include=hidden") : null;

        return $page instanceof Page && $page->template()->hasUrl() ? $page : null;
    }

    /** Browsers send Sec-Fetch-Site; older ones an Origin, which must be this site. */
    private function isSameSite(Request $request): bool
    {
        $site = $request->headers->get('Sec-Fetch-Site');

        if ($site !== null) {
            return $site === 'same-origin';
        }

        $origin = $request->headers->get('Origin');

        return $origin !== null && parse_url($origin, PHP_URL_HOST) === $request->getHost();
    }

    /** Past the limit nothing more is recorded this minute; the address is kept only as a hash for that minute. */
    private function withinLimit(Request $request): bool
    {
        $key = 'stats-hit:'.hash('xxh128', (string) $request->ip());

        return RateLimiter::attempt($key, (int) config('stats.hits_per_minute', 120), fn (): bool => true, self::SECONDS_PER_MINUTE);
    }
}
