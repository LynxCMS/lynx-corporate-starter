<?php

use App\Search\SiteSearch;
use Livewire\Component;
use LynxCms\Core\Livewire\UsesLynxPage;

/*
 * The search panel's live results as a Livewire component, the other version of
 * resources/js/search.js (SITE_SEARCH_PANEL=livewire). It sits in a Lynx island, so pages stay in
 * the page cache, and UsesLynxPage keeps its requests in the visitor's language. Its results are
 * the same view, and opening one counts the same way.
 */
new class extends Component
{
    use UsesLynxPage;

    public string $query = '';

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = SiteSearch::query($this->query);

        return [
            'query' => $query,
            'results' => SiteSearch::live($query),
            'shown' => SiteSearch::SHOWN,
            'search' => lynx()->pages->findOne('template=search'),
        ];
    }
};
?>

<div class="grid content-start gap-8" data-search-livewire>
    @if ($search)
        @include('search.form', ['search' => $search, 'live' => true])
    @endif
    <div aria-live="polite">
        @include('search.live')
    </div>
</div>
