{{-- The search panel's live results (LiveSearchController): the first matches, and a link to all of them. --}}
@if ($results !== null)
    <div data-live-results="{{ $query }}">
        <p class="text-sm font-semibold" role="status">{{ trans_choice('site.search_found', $results->count() > $shown ? $shown : $results->count(), ['count' => $results->count() > $shown ? $shown.'+' : $results->count(), 'query' => $query]) }}</p>
        @if ($results->count() > 0)
            <ul class="mt-4 grid gap-1 border-t border-line-strong pt-3">
                @foreach (array_slice($results->all(), 0, $shown) as $result)
                    <li data-search-hit="{{ $result->id }}">
                        <a href="{{ $result->url() }}" class="group block py-1.5">
                            <span class="block text-[0.9375rem] group-hover:underline">{{ $result->title }}</span>
                            <span class="eyebrow block">{{ $result->parent()?->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($results->count() > $shown && $search)
            <a href="{{ $search->url() }}?q={{ urlencode($query) }}" class="link-arrow mt-4 text-sm" data-search-all>{{ __('site.search_all') }}</a>
        @endif
    </div>
@endif
