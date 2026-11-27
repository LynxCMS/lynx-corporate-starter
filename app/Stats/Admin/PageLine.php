<?php

declare(strict_types=1);

namespace App\Stats\Admin;

use App\Stats\PageStats;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One line in the page editor's header (AdminSlots::EDITOR_HEADER): the page's views and, when it
 * has any, downloads in the last 30 days.
 */
final class PageLine extends Component
{
    #[Locked]
    public int $pageId;

    public function mount(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function render(PageStats $stats): View
    {
        return view('stats.page-line', [
            'views' => $stats->totals([$this->pageId], PageStats::VIEW, TopLists::DAYS)[$this->pageId] ?? 0,
            'downloads' => $stats->totals([$this->pageId], PageStats::DOWNLOAD, TopLists::DAYS)[$this->pageId] ?? null,
            'days' => TopLists::DAYS,
        ]);
    }
}
