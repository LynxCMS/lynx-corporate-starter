{{--
    The search panel's form, opening the Search page. Both versions of the panel use it, and so does
    the Livewire island while it loads. $live binds the field to the Livewire component.
--}}
<form method="GET" action="{{ $search->url() }}" role="search" class="flex">
    <label for="search-panel-q" class="sr-only">{{ __('site.search_label') }}</label>
    <input id="search-panel-q" type="search" name="q" autocomplete="off" placeholder="{{ __('site.search_placeholder') }}" data-search-input
           @if ($live ?? false) wire:model.live.debounce.250ms="query" @endif
           class="min-w-0 flex-1 border-b-2 border-ink bg-canvas px-4 py-3 text-lg outline-none focus:border-signal">
    <button type="submit" class="button">{{ __('site.search_go') }}</button>
</form>
