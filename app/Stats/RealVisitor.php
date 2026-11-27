<?php

declare(strict_types=1);

namespace App\Stats;

use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * Whether a request comes from a person's browser rather than a crawler, a link previewer, a
 * monitor or a prefetch, judged by its user agent (CrawlerDetect's list) and its method and headers.
 */
final readonly class RealVisitor
{
    public static function is(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent !== ''
            && ! $request->isMethod('HEAD')
            && ! self::isPrefetch($request)
            && ! new CrawlerDetect(userAgent: $agent)->isCrawler();
    }

    private static function isPrefetch(Request $request): bool
    {
        $purpose = strtolower($request->headers->get('Sec-Purpose', $request->headers->get('Purpose', '')) ?? '');

        return str_contains($purpose, 'prefetch');
    }
}
