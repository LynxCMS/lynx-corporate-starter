<?php

declare(strict_types=1);

namespace App\Stats;

use App\Stats\Admin\PageLine;
use App\Stats\Console\FakeHistory;
use App\Stats\Console\PruneLinks;
use App\Stats\Http\DownloadFileController;
use App\Stats\Http\HitController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use LynxCms\Admin\AdminAccess;
use LynxCms\Admin\AdminSlots;
use LynxCms\Admin\Home\HomeScreen;
use LynxCms\Contracts\Metrics\PageMetrics;

/**
 * Visitor counts (see README.md): the beacon's and the download links' routes, outside the web
 * middleware group so they start no session and set no cookie; the page editor's line and the
 * admin home's lists; the commands and the daily cleanup; and the counts as Lynx's page metrics.
 */
final class StatsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Lynx reads the counts as page metrics: Insights ranks and reports by views (ADR-0156).
        $this->app->bind(PageMetrics::class, LynxPageMetrics::class);
    }

    public function boot(): void
    {
        Route::post('/stats/hit', HitController::class)->name('stats.hit');
        Route::get('/stats/file/{page}/{field}/{file}', DownloadFileController::class)
            ->whereNumber('page')->where('file', '[^/]+')->name('stats.file');

        $this->admin();

        if ($this->app->runningInConsole()) {
            $this->commands([FakeHistory::class, PruneLinks::class]);
        }

        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule->command('stats:prune')->daily());
    }

    private function admin(): void
    {
        Livewire::component('stats-page-line', PageLine::class);
        $mayEdit = fn (): bool => app(AdminAccess::class)->allows('pages.edit');

        app(AdminSlots::class)->add(AdminSlots::EDITOR_HEADER, 'stats-page-line');

        $home = app(HomeScreen::class);
        $home->stat('stats-views-today', __('stats.views_today'), fn (): int => app(PageStats::class)->sum(PageStats::VIEW, 1), icon: 'eye', visible: $mayEdit, order: 60);
        $home->panel('stats.home.top-pages', visible: $mayEdit, order: 30, key: 'stats-pages');
        $home->panel('stats.home.top-files', visible: $mayEdit, order: 30, key: 'stats-files', column: HomeScreen::SIDE);
    }
}
