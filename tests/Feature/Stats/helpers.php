<?php

declare(strict_types=1);

use App\Stats\PageStats;
use LynxCms\Core\Pages\Page;

const CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

/** A published download page of the seeded site. */
function aDownload(): Page
{
    $page = lynx()->pages->findOne('template=download');
    assert($page instanceof Page);

    return $page;
}

function statTotal(int $page, string $event): int
{
    return app(PageStats::class)->totals([$page], $event)[$page] ?? 0;
}
