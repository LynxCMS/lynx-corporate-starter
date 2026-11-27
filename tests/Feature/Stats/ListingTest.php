<?php

declare(strict_types=1);

use App\Downloads\DownloadListing;
use App\Stats\PageStats;
use App\Stats\Ranking;
use Database\Seeders\Support\Artwork;
use Database\Seeders\Support\Downloads;
use Illuminate\Testing\TestResponse;
use LynxCms\Cache\Pages\TemplateRules;

/*
 * The resources list: featured downloads, then Trending lately (downloads lately, decayed), Most downloaded, Newest and A to Z, 24 a page, counts
 * on cards, and 404 for anything else, so the page cache keeps only real pages.
 */

/** A data attribute's values in the list itself, after the featured section. */
function listValues(TestResponse $response, string $attribute): array
{
    $html = (string) $response->getContent();
    preg_match_all('/\b'.preg_quote($attribute, '/').'="([^"]*)"/', substr($html, (int) strpos($html, 'data-list>')), $matches);

    return $matches[1];
}

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

    expect(listValues($response, 'data-downloads'))->toBe(['1234', '0'])
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
        ->and(count(listValues($first, 'data-card')))->toBe(24)
        ->and(count(listValues($second, 'data-card')))->toBe(8);
});

it('answers 404 for an unknown sort or a page past the last', function (string $query): void {
    $this->get('/resources/?'.$query)->assertNotFound();
})->with(['sort=random', 'page=2', 'page=0', 'page=x', 'sort[]=title']);

it('keeps a cached copy per sort and page of the list', function (): void {
    expect(app(TemplateRules::class)->for('downloads')?->parameters)->toBe(['sort', 'page'])
        ->and(config('lynx-cache.pages.max_variants'))->toBeGreaterThanOrEqual(75);
});

it('sorts by all-time downloads, where Trending lately favours recent ones', function (): void {
    [$newest, $older] = downloadIds();
    $stats = app(PageStats::class);
    $stats->record($older, PageStats::DOWNLOAD, 300, day: now()->subDays(80));
    $stats->record($newest, PageStats::DOWNLOAD, 20);

    $most = $this->get('/resources/?sort=downloads')->assertOk();
    $popular = $this->get('/resources/')->assertOk();

    expect(listValues($most, 'data-downloads'))->toBe(['300', '20'])
        ->and(listValues($popular, 'data-downloads'))->toBe(['20', '300']);
});

it('shows the featured downloads first, in the editor\'s order, on the first page only', function (): void {
    foreach (range(1, 30) as $number) {
        Downloads::create(sprintf('Report %02d', $number), 'report', ['en']);
    }
    $list = lynx()->pages->get('/resources/');
    $picked = array_slice(downloadIds(), 3, 4);
    $list->featured_downloads = $picked;
    $list->save();

    $first = $this->get('/resources/')->assertOk();
    $second = $this->get('/resources/?page=2')->assertOk();

    expect(dataValues($first, 'data-featured-item'))->toBe(array_map('strval', $picked))
        ->and(dataValues($second, 'data-featured-item'))->toBe([]);
});

it('shows a featured download\'s picture as its thumbnail, and a cover with its kind when it has none', function (): void {
    $pictured = Downloads::create('Pictured report', 'report', ['en']);
    $plain = Downloads::create('Plain report', 'report', ['en']);
    config(['site.artwork' => true]);
    Artwork::attach($pictured);
    $list = lynx()->pages->get('/resources/');
    $list->featured_downloads = [$pictured->id, $plain->id];
    $list->save();

    $response = $this->get('/resources/')->assertOk();

    expect(dataValues($response, 'data-featured-thumb'))->toBe([(string) $pictured->id])
        ->and(dataValues($response, 'data-featured-cover'))->toBe([(string) $plain->id])
        ->and(listValues($response, 'data-featured-thumb'))->toBe([]);
});

it('features the seeded downloads', function (): void {
    expect(lynx()->pages->get('/resources/')?->featured_downloads->count())->toBe(2);
});

it('lists the downloads when the Resources page has no featured field', function (): void {
    lynx()->templates->update('downloads', ['remove' => ['featured_downloads']]);

    $response = $this->get('/resources/')->assertOk();

    expect(dataValues($response, 'data-featured-item'))->toBe([])
        ->and(listValues($response, 'data-card'))->not->toBeEmpty();
});
