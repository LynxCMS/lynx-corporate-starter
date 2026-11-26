{{--
    The resources list: the downloads an editor features (up to 4, on the first page, with their
    pictures as thumbnails), then every download as cards, 24 a page, sorted Trending lately
    (downloads lately), Most downloaded (all time), Newest or A to Z (App\Downloads\DownloadListing),
    each with its download count. An unknown sort or a page past the last is a 404, so the page
    cache only keeps real pages. Sort and page links are nofollow; the canonical address
    (lynxcms/seo) is the list without them.
--}}
@php
    $sort = request()->query('sort', \App\Downloads\DownloadListing::DEFAULT_SORT);
    $number = request()->query('page', '1');
    $listing = is_string($sort) && is_string($number) && ctype_digit($number)
        ? app(\App\Downloads\DownloadListing::class)->for($sort, (int) $number)
        : null;
    abort_if($listing === null, 404);
    // Empty on later pages, and on a site whose Resources page doesn't have the field yet.
    $featured = $listing['page'] === 1 ? [...($page->featured_downloads ?? [])] : [];
    $ids = array_map(fn ($item) => (int) $item->id, [...$listing['items'], ...$featured]);
    $downloads = app(\App\Stats\PageStats::class)->totals($ids, \App\Stats\PageStats::DOWNLOAD);
    $listUrl = $page->url();
    $link = fn (string $order, int $number = 1): string => rtrim($listUrl.'?'.http_build_query(array_filter([
        'sort' => $order === \App\Downloads\DownloadListing::DEFAULT_SORT ? null : $order,
        'page' => $number === 1 ? null : $number,
    ])), '?');
@endphp
<x-layout :page="$page">
    <header class="bg-mist">
        <div class="shell py-16">
            <h1 class="text-display font-light">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->summary }}</p>
            @endif
        </div>
    </header>
    <div class="shell py-16">
        @if (count($featured) > 0)
            <section class="mb-16" aria-labelledby="featured-heading" data-featured>
                <h2 id="featured-heading" class="mb-6 text-2xl font-light">{{ __('site.listing.featured') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                    @foreach ($featured as $child)
                        <x-featured-download :page="$child" :downloads="$downloads[(int) $child->id] ?? 0" />
                    @endforeach
                </div>
            </section>
        @endif
        <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm" aria-label="{{ __('site.listing.sort') }}" data-sorts>
            @foreach (\App\Downloads\DownloadListing::SORTS as $option)
                <a href="{{ $link($option) }}" rel="nofollow" data-sort="{{ $option }}"
                   @if ($option === $listing['sort']) aria-current="true" @endif
                   @class(['border px-3 py-1.5', 'border-ink bg-ink text-white' => $option === $listing['sort'], 'border-line hover:border-ink' => $option !== $listing['sort']])>{{ __('site.listing.'.$option) }}</a>
            @endforeach
        </nav>
        <div class="grid gap-px bg-line sm:grid-cols-2 lg:grid-cols-3" data-list>
            @foreach ($listing['items'] as $child)
                <x-card :page="$child" :downloads="$downloads[(int) $child->id] ?? 0" />
            @endforeach
        </div>
        @if ($listing['last'] > 1)
            <nav class="mt-10 flex items-center justify-between gap-4 text-sm" aria-label="{{ __('site.listing.pages') }}" data-pager>
                @if ($listing['page'] > 1)
                    <a href="{{ $link($listing['sort'], $listing['page'] - 1) }}" rel="nofollow prev" class="border border-line px-3 py-1.5 hover:border-ink" data-page-link="{{ $listing['page'] - 1 }}">{{ __('site.listing.previous') }}</a>
                @else
                    <span></span>
                @endif
                <span class="text-ink-soft" data-page="{{ $listing['page'] }}" data-last="{{ $listing['last'] }}">{{ __('site.listing.page_of', ['page' => $listing['page'], 'last' => $listing['last']]) }}</span>
                @if ($listing['page'] < $listing['last'])
                    <a href="{{ $link($listing['sort'], $listing['page'] + 1) }}" rel="nofollow next" class="border border-line px-3 py-1.5 hover:border-ink" data-page-link="{{ $listing['page'] + 1 }}">{{ __('site.listing.next') }}</a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    </div>
</x-layout>
