<?php

declare(strict_types=1);

use App\Models\User;
use App\Stats\Admin\PageLine;
use App\Stats\PageStats;
use Livewire\Livewire;

/*
 * Counts in the admin: a line in the page editor's header, and the home screen's lists of the most
 * visited pages and the most downloaded files of the last 30 days.
 */

function owner(): User
{
    return User::query()->where('email', 'owner@arcturus.example')->firstOrFail();
}

it('shows a page\'s views and downloads in the editor\'s header', function (): void {
    $id = (int) aDownload()->id;
    app(PageStats::class)->record($id, PageStats::VIEW, 40);
    app(PageStats::class)->record($id, PageStats::DOWNLOAD, 3, subject: 'en');
    app(PageStats::class)->record($id, PageStats::VIEW, 99, day: now()->subDays(45));

    Livewire::actingAs(owner())->test(PageLine::class, ['pageId' => $id])
        ->assertSeeHtml('data-views="40"')
        ->assertSeeHtml('data-downloads="3"');
});

it('shows views only for a page nobody downloads from', function (): void {
    Livewire::actingAs(owner())->test(PageLine::class, ['pageId' => (int) home()->id])
        ->assertSeeHtml('data-views="0"')
        ->assertDontSeeHtml('data-downloads=');
});

it('lists the most visited pages and the most downloaded files on the admin home', function (): void {
    $download = (int) aDownload()->id;
    $home = (int) home()->id;
    $stats = app(PageStats::class);
    $stats->record($home, PageStats::VIEW, 50);
    $stats->record($download, PageStats::VIEW, 20);
    $stats->record($download, PageStats::DOWNLOAD, 7, subject: 'pt');
    $stats->record(999999, PageStats::VIEW, 500);

    $response = $this->actingAs(owner())->followingRedirects()->get('/lynx')->assertOk();

    expect(dataValues($response, 'data-home-panel'))->toContain('stats-pages', 'stats-files')
        ->and(dataValues($response, 'data-stats-row'))->toBe([(string) $home, (string) $download, (string) $download])
        ->and(dataValues($response, 'data-stats-total'))->toBe(['50', '20', '7'])
        ->and(dataValues($response, 'data-stats-subject'))->toBe(['pt']);
});

it('shows empty lists before anything is counted', function (): void {
    $response = $this->actingAs(owner())->followingRedirects()->get('/lynx')->assertOk();

    expect(dataValues($response, 'data-home-panel'))->toContain('stats-pages', 'stats-files')
        ->and(dataValues($response, 'data-stats-row'))->toBe([]);
});
