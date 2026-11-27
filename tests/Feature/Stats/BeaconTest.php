<?php

declare(strict_types=1);

use App\Stats\PageStats;
use Illuminate\Testing\TestResponse;

/*
 * The beacon (/stats/hit): a view or a declared event, counted for real visitors on published pages
 * with an address, from the site itself, never for signed-in editors. Always 204, and no cookie.
 */

function beacon(array $data, array $headers = []): TestResponse
{
    return test()->withHeaders($headers + ['User-Agent' => CHROME, 'Sec-Fetch-Site' => 'same-origin'])->post('/stats/hit', $data);
}

it('counts a view of a published page and sets no cookie', function (): void {
    $id = (int) aDownload()->id;

    $response = beacon(['page' => $id, 'event' => 'view']);

    $response->assertNoContent();
    expect($response->headers->getCookies())->toBe([])
        ->and(statTotal($id, PageStats::VIEW))->toBe(1);
});

it('counts every view, as visits', function (): void {
    $id = (int) aDownload()->id;
    beacon(['page' => $id]);
    beacon(['page' => $id]);

    expect(statTotal($id, PageStats::VIEW))->toBe(2);
});

it('counts an event the site declares, with its subject', function (): void {
    $id = (int) home()->id;

    beacon(['page' => $id, 'event' => 'hero-cta', 'subject' => 'contact']);

    expect(PageStats::connection()->table('stat_counts')->where(['page_id' => $id, 'event' => 'hero-cta', 'subject' => 'contact'])->value('count'))->toBe(1);
});

it('drops a subject that is not a plain label', function (): void {
    $id = (int) home()->id;

    beacon(['page' => $id, 'event' => 'hero-cta', 'subject' => 'ana@example.com']);

    expect(PageStats::connection()->table('stat_counts')->where(['page_id' => $id, 'event' => 'hero-cta'])->value('subject'))->toBe('');
});

it('counts nothing that is not a real view', function (array $data, array $headers): void {
    beacon($data + ['page' => (int) aDownload()->id], $headers)->assertNoContent();

    expect(PageStats::connection()->table('stat_counts')->count())->toBe(0);
})->with([
    'a crawler' => [[], ['User-Agent' => GOOGLEBOT]],
    'no user agent' => [[], ['User-Agent' => '']],
    'another site' => [[], ['Sec-Fetch-Site' => 'cross-site']],
    'an unknown event' => [['event' => 'scroll'], []],
]);

it('counts nothing for a signed-in editor', function (): void {
    test()->withUnencryptedCookie('lynx_bypass', '1');

    beacon(['page' => (int) aDownload()->id])->assertNoContent();

    expect(PageStats::connection()->table('stat_counts')->count())->toBe(0);
});

it('counts nothing for unknown or unpublished pages', function (): void {
    $page = aDownload();
    lynx()->pages->unpublish($page);

    beacon(['page' => (int) $page->id])->assertNoContent();
    beacon(['page' => 999999])->assertNoContent();
    beacon(['page' => 'x'])->assertNoContent();

    expect(PageStats::connection()->table('stat_counts')->count())->toBe(0);
});

it('puts the beacon on every page, and marks the hero\'s button as an event', function (): void {
    $response = $this->get('/')->assertOk();

    expect(dataValues($response, 'data-stats-beacon'))->toBe([(string) home()->id])
        ->and(dataValues($response, 'data-stats-event'))->toContain('hero-cta');
});
