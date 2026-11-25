<?php

declare(strict_types=1);

use App\Stats\FakeHistory;
use App\Stats\PageStats;
use Carbon\CarbonImmutable;

/*
 * Made-up history for testing (stats:fake-history, site:seed-downloads): nothing before a page is
 * published, quieter weekends, the same history for the same seed, never in production unless forced.
 */

it('writes nothing before a page is published', function (): void {
    $published = CarbonImmutable::today()->subDays(10);
    $rows = (new FakeHistory(90, seed: 7))->rows(1, $published, ['en', 'pt']);

    expect(collect($rows)->min('day'))->toBeGreaterThanOrEqual($published->toDateString())
        ->and(collect($rows)->pluck('subject')->unique()->sort()->values()->all())->toContain('en');
});

it('makes weekends quieter than weekdays across a site', function (): void {
    $history = new FakeHistory(120, seed: 3);
    $rows = collect(range(1, 40))->flatMap(fn (int $page): array => $history->rows($page, null))->where('event', PageStats::VIEW);
    $weekend = $rows->filter(fn (array $row): bool => CarbonImmutable::parse($row['day'])->isWeekend());
    $weekday = $rows->reject(fn (array $row): bool => CarbonImmutable::parse($row['day'])->isWeekend());

    expect($weekend->sum('count') / max(1, $weekend->pluck('day')->unique()->count()))
        ->toBeLessThan(0.8 * $weekday->sum('count') / max(1, $weekday->pluck('day')->unique()->count()));
});

it('gives the same history for the same seed', function (): void {
    expect((new FakeHistory(30, seed: 11))->rows(5, null))->toBe((new FakeHistory(30, seed: 11))->rows(5, null));
});

it('fills the history of the pages a selector finds', function (): void {
    $this->artisan('stats:fake-history', ['selector' => 'template=download', '--days' => 20, '--seed' => 1])->assertSuccessful();

    $pages = PageStats::connection()->table('stat_counts')->distinct()->pluck('page_id')->all();
    $downloads = array_map('intval', lynx()->pages->find('template=download')->ids());

    expect($pages)->not->toBeEmpty()
        ->and(array_diff($pages, $downloads))->toBe([]);
});

it('makes many download pages with history', function (): void {
    $before = lynx()->pages->find('template=download')->count();

    $this->artisan('site:seed-downloads', ['--count' => 12, '--days' => 10, '--seed' => 2])->assertSuccessful();

    expect(lynx()->pages->find('template=download')->count())->toBe($before + 12)
        ->and(PageStats::connection()->table('stat_counts')->where('event', PageStats::DOWNLOAD)->count())->toBeGreaterThan(0);
});

it('refuses to make up counts in production unless forced', function (string $command, array $arguments): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan($command, $arguments)->assertFailed();
})->with([
    'fake history' => ['stats:fake-history', ['selector' => 'template=download']],
    'seed downloads' => ['site:seed-downloads', ['--count' => 1]],
]);
