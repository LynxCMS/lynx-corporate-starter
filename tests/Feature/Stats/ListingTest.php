<?php

declare(strict_types=1);

use App\Downloads\DownloadListing;
use App\Stats\PageStats;
use App\Stats\Ranking;
use Database\Seeders\Support\Downloads;
use LynxCms\Cache\Pages\TemplateRules;

/*
 * The resources list: Popular (downloads lately, decayed), Newest and A to Z, 24 a page, counts
 * on cards, and 404 for anything else, so the page cache keeps only real pages.
 */

function downloadIds(): array
{
    return array_values(array_map('intval', lynx()->pages->find('template=download, sort=-published_on')->ids()));
}

it('ranks recent downloads above more, older ones, and keeps the given order for the rest', function (): void {
    [$a, $b] = downloadIds();
    $stats = app(PageStats::class);
    $stats->record($a, PageStats::DOWNLOAD, 30, day: now()->subDays(40));
    $stats->record($b, PageStats::DOWNLOAD, 5, day: now()->subDay());

    expect(app(Ranking::class)->ids([$a, $b, 999], PageStats::DOWNLOAD))->toBe([$b, $a, 999]);

    config(['stats.half_life_days' => 60]);

    expect(app(Ranking::class)->ids([$a, $b], PageStats::DOWNLOAD))->toBe([$a, $b]);
});

it('lists downloads by popularity, with their counts on the cards', function (): void {
    [$newest, $older] = downloadIds();
    app(PageStats::class)->record($older, PageStats::DOWNLOAD, 1234);

    $response = $this->get('/resources/')->assertOk();

    expect(dataValues($response, 'data-downloads'))->toBe(['1234', '0'])
        ->and($response->getContent())->toContain('1.2K downloads')
        ->and(dataValues($response, 'data-sort'))->toBe(DownloadListing::SORTS);
});

it('sorts by title and pages through the list', function (): void {
    foreach (range(1, 30) as $number) {
        Downloads::create(sprintf('Report %02d', $number), 'report', ['en']);
    }

    $first = $this->get('/resources/?sort=title')->assertOk();
    $second = $this->get('/resources/?sort=title&page=2')->assertOk();

    expect(dataValues($first, 'data-last'))->toBe(['2'])
        ->and(dataValues($second, 'data-page'))->toBe(['2'])
        ->and(count(dataValues($first, 'data-card')))->toBe(24)
        ->and(count(dataValues($second, 'data-card')))->toBe(8);
});

it('answers 404 for an unknown sort or a page past the last', function (string $query): void {
    $this->get('/resources/?'.$query)->assertNotFound();
})->with(['sort=random', 'page=2', 'page=0', 'page=x', 'sort[]=title']);

it('keeps a cached copy per sort and page of the list', function (): void {
    expect(app(TemplateRules::class)->for('downloads')?->parameters)->toBe(['sort', 'page'])
        ->and(config('lynx-cache.pages.max_variants'))->toBeGreaterThanOrEqual(75);
});
