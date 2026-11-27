<?php

declare(strict_types=1);

use App\Stats\LynxPageMetrics;
use App\Stats\PageStats;
use LynxCms\Contracts\Metrics\PageMetrics;
use LynxCms\Insights\Models\InsightReport;

/*
 * The site's counts, given to Lynx as page metrics (ADR-0156), so Insights ranks missing
 * translations by views and reports neglected popular pages.
 */

beforeEach(function (): void {
    PageStats::connection()->table('stat_counts')->delete();
});

it('gives Lynx the site\'s counts as page metrics', function (): void {
    expect(app(PageMetrics::class))->toBeInstanceOf(LynxPageMetrics::class);
});

it('totals a page\'s views over the last days', function (): void {
    $id = (int) aDownload()->id;
    app(PageStats::class)->record($id, PageStats::VIEW, 40);
    app(PageStats::class)->record($id, PageStats::VIEW, 99, day: now()->subDays(45));

    expect(app(PageMetrics::class)->totals([$id], PageMetrics::VIEW, 30))->toBe([$id => 40]);
});

it('lists the most viewed pages, highest first', function (): void {
    [$first, $second] = lynx()->pages->find('template=download, limit=2')->ids();
    app(PageStats::class)->record($first, PageStats::VIEW, 5);
    app(PageStats::class)->record($second, PageStats::VIEW, 50);

    expect(app(PageMetrics::class)->top(PageMetrics::VIEW, 30, 2))->toBe([$second => 50, $first => 5]);
});

it('knows the first day with a count, or none', function (): void {
    expect(app(PageMetrics::class)->since())->toBeNull();

    app(PageStats::class)->record((int) aDownload()->id, PageStats::VIEW, 1, day: now()->subDays(20));

    expect(app(PageMetrics::class)->since()?->format('Y-m-d'))->toBe(now()->subDays(20)->toDateString());
});

it('writes an insights report with the seeded site', function (): void {
    $this->artisan('lynx:insights')->assertSuccessful();

    expect(InsightReport::query()->latest('id')->firstOrFail()->failures)->toBe([]);
});

it('seeds a first insights report', function (): void {
    expect(InsightReport::query()->count())->toBeGreaterThan(0);
});
