{{-- The cards of a visitor's bookmarks, fetched by resources/js/bookmarks.js. --}}
@foreach ($pages as $page)
    <div class="relative ring-1 ring-line" data-bookmark-card="{{ $page->id }}">
        <x-card :page="$page" />
        <button type="button" class="absolute right-3 top-3 z-10 grid size-9 place-items-center bg-canvas/90 ring-1 ring-line hover:bg-mist" data-bookmark-remove="{{ $page->id }}" aria-label="{{ __('site.bookmark_remove', ['title' => $page->title]) }}">
            <svg viewBox="0 0 20 20" class="size-4 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15"/></svg>
        </button>
    </div>
@endforeach
