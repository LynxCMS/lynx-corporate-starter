{{--
    Search: the words in ?q looked up in Lynx's search index, in the visitor's language. Opening a
    result counts towards the search panel's suggestions (data-search-hit, resources/js/search.js).
    Without words, the page shows the suggestions. Never stored by the page cache.
--}}
@php
    // A visitor can send an array (?q[]=x), which (string) would turn into an error.
    $query = request()->query('q');
    $query = is_string($query) ? trim(Str::limit($query, 100, '')) : '';
    // Quotes would end the selector's value; words are what the index matches anyway.
    $words = trim(preg_replace('/["\\\\]+/', ' ', $query) ?? '');
    $results = $words === '' ? null : lynx()->pages->find('search*="'.$words.'", limit=30');
    $suggestions = $results === null ? app(\App\Search\Suggestions::class)->for($page) : [];
@endphp
<x-layout :page="$page">
    <header class="bg-mist">
        <div class="shell py-16">
            <h1 class="text-display font-light">{{ $page->title }}</h1>
            <form method="GET" action="{{ $page->url() }}" role="search" class="mt-8 flex max-w-3xl">
                <label for="search-q" class="sr-only">{{ __('site.search_label') }}</label>
                <input id="search-q" type="search" name="q" value="{{ $query }}" autocomplete="off" placeholder="{{ __('site.search_placeholder') }}" @if ($query === '') autofocus @endif
                       class="min-w-0 flex-1 border-b-2 border-ink bg-canvas px-4 py-3 text-lg outline-none focus:border-signal">
                <button type="submit" class="button">{{ __('site.search_go') }}</button>
            </form>
        </div>
    </header>
    <div class="shell py-16">
        @if ($results !== null)
            <p class="text-ink-soft" role="status">{{ trans_choice('site.search_found', $results->count(), ['count' => $results->count(), 'query' => $query]) }}</p>
            <div class="mt-8 grid gap-px bg-line sm:grid-cols-2 lg:grid-cols-3" data-search-results>
                @foreach ($results as $result)
                    <div class="ring-1 ring-line" data-search-hit="{{ $result->id }}"><x-card :page="$result" /></div>
                @endforeach
            </div>
        @elseif ($suggestions !== [])
            <h2 class="text-xl">{{ __('site.suggestions') }}</h2>
            <div class="mt-8 grid gap-px bg-line sm:grid-cols-2 lg:grid-cols-3" data-suggestions>
                @foreach ($suggestions as $suggestion)
                    <div class="ring-1 ring-line"><x-card :page="$suggestion" /></div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
