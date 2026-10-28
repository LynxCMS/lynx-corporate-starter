@props(['page'])
{{--
    Bookmark this page, and the link to My bookmarks with how many there are. Both work in the
    browser (resources/js/bookmarks.js, localStorage), so they start hidden and the cached HTML is
    the same for everyone: only the page's id is in it.
--}}
@php
    $bookmarks = lynx()->pages->findOne('template=bookmarks');
    $canBookmark = $page->template()->hasUrl() && ! in_array($page->template->name, ['search', 'bookmarks', 'not-found'], true);
@endphp
@if ($bookmarks)
    @if ($canBookmark)
        <button type="button" class="group grid size-10 place-items-center hover:bg-mist aria-pressed:text-signal" data-bookmark-toggle="{{ $page->id }}" aria-pressed="false"
                aria-label="{{ __('site.bookmark_add') }}" data-label-saved="{{ __('site.bookmark_saved') }}" hidden>
            <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5] group-aria-pressed:fill-current" aria-hidden="true"><path d="M5.5 3h9v14l-4.5-3.5L5.5 17z"/></svg>
        </button>
    @endif
    <a href="{{ $bookmarks->url() }}" class="relative grid size-10 place-items-center hover:bg-mist" data-bookmarks-link aria-label="{{ $bookmarks->title }}">
        <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M4 4h12M4 10h12M4 16h7"/></svg>
        <span class="absolute right-0.5 top-0.5 min-w-4 bg-signal px-1 text-center text-[0.6875rem] leading-4 text-white" data-bookmarks-count hidden></span>
    </a>
@endif
