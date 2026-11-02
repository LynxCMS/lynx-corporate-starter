<?php

use App\Search\SiteSearch;
use Livewire\Attributes\Locked;
use Livewire\Component;
use LynxCms\Core\Livewire\UsesLynxPage;
use LynxCms\Core\Pages\Page;

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

    /** The Search page the panel already found, so the first render needn't look it up again. */
    #[Locked]
    public ?int $searchId = null;

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = SiteSearch::query($this->query);

        return [
            'query' => $query,
            'results' => SiteSearch::live($query),
            'shown' => SiteSearch::SHOWN,
            'search' => $this->searchPage(),
        ];
    }

    private function searchPage(): ?Page
    {
        return $this->searchId === null ? lynx()->pages->findOne('template=search') : lynx()->pages->get($this->searchId);
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
