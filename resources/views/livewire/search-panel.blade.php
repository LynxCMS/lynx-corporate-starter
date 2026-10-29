{{-- The Livewire version of the search panel's field and live results (App\Livewire\SearchPanel). --}}
<div class="grid content-start gap-8" data-search-livewire>
    @if ($search)
        @include('search.form', ['search' => $search, 'live' => true])
    @endif
    <div aria-live="polite">
        @include('search.live')
    </div>
</div>
