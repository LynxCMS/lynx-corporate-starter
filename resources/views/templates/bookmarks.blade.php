{{--
    My bookmarks: the pages a visitor saved, kept in their browser. resources/js/bookmarks.js reads
    the list and asks /bookmarks/cards for the cards. A link with ?items= opens someone else's list,
    with a button to save it, which is also how a list moves to another device.
--}}
<x-layout :page="$page">
    <header class="bg-mist">
        <div class="shell py-16">
            <h1 class="text-display font-light">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->summary }}</p>
            @endif
        </div>
    </header>
    <div class="shell py-16" data-bookmarks-page data-cards-url="{{ route('bookmarks.cards') }}" data-lang="{{ lynx()->languages->current()->code }}">
        <div class="mb-8 flex flex-wrap items-center gap-4 bg-mist p-6" data-bookmarks-shared hidden>
            <p class="flex-1">{{ __('site.bookmarks_shared') }}</p>
            <button type="button" class="button" data-bookmarks-save-all>{{ __('site.bookmarks_save_all') }}</button>
        </div>
        <p class="text-lg text-ink-soft" data-bookmarks-empty hidden>{{ __('site.bookmarks_empty') }}</p>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-bookmarks-list></div>
        <div class="mt-10 flex flex-wrap items-center gap-4" data-bookmarks-share-row hidden>
            <button type="button" class="button" data-bookmarks-share>{{ __('site.bookmarks_share') }}</button>
            <input type="text" readonly class="min-w-0 flex-1 border border-line bg-canvas px-3 py-2 text-sm" data-bookmarks-share-url hidden aria-label="{{ __('site.bookmarks_share') }}">
            <span class="text-sm text-ink-soft" role="status" data-bookmarks-copied hidden>{{ __('site.bookmarks_copied') }}</span>
        </div>
    </div>
</x-layout>
