<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Search\SiteSearch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use LynxCms\Core\Livewire\UsesLynxPage;

/**
 * The search panel's live results as a Livewire component, the other version of
 * resources/js/search.js (SITE_SEARCH_PANEL=livewire). It sits in a Lynx island, so pages stay in
 * the page cache, and UsesLynxPage keeps its requests in the visitor's language. Its results are
 * the same view, and opening one counts the same way.
 */
final class SearchPanel extends Component
{
    use UsesLynxPage;

    public string $query = '';

    public function render(): View
    {
        $query = SiteSearch::query($this->query);

        return view('livewire.search-panel', [
            'query' => $query,
            'results' => SiteSearch::live($query),
            'shown' => SiteSearch::SHOWN,
            'search' => lynx()->pages->findOne('template=search'),
        ]);
    }
}
