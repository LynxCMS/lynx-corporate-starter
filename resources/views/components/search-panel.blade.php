{{--
    The search icon in the header: it opens a panel like the mega-menu's (and closes it, both being
    <details name="mega">) with a search box and the Search page's suggestions. The suggestions are
    the same for every visitor, so they are kept as a page part for ten minutes, or until the Search
    page changes. The form stays outside it.
--}}
@php($search = lynx()->pages->findOne('template=search'))
@if ($search)
    <details name="mega" class="group" data-menu="search">
        <summary class="grid size-10 cursor-pointer list-none place-items-center hover:bg-mist group-open:bg-mist [&::-webkit-details-marker]:hidden" aria-label="{{ __('site.search_open') }}">
            <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5] group-open:hidden" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M13 13l4.5 4.5"/></svg>
            <svg viewBox="0 0 20 20" class="hidden size-5 fill-none stroke-current stroke-[1.5] group-open:block" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15"/></svg>
        </summary>
        <div class="absolute inset-x-0 top-14 border-b border-line bg-mist shadow-[0_12px_24px_-16px_rgb(0_0_0/0.35)]" data-search-panel>
            <div class="shell grid gap-10 py-10 lg:grid-cols-16">
                <form method="GET" action="{{ $search->url() }}" role="search" class="flex self-start lg:col-span-9">
                    <label for="search-panel-q" class="sr-only">{{ __('site.search_label') }}</label>
                    <input id="search-panel-q" type="search" name="q" autocomplete="off" placeholder="{{ __('site.search_placeholder') }}" data-search-input
                           class="min-w-0 flex-1 border-b-2 border-ink bg-canvas px-4 py-3 text-lg outline-none focus:border-signal">
                    <button type="submit" class="button">{{ __('site.search_go') }}</button>
                </form>
                @lynxCache('search-suggestions', ['dependsOn' => ['template:search'], 'ttl' => 600])
                    @php($suggestions = app(\App\Search\Suggestions::class)->for($search))
                    @if ($suggestions !== [])
                        <div class="lg:col-span-6 lg:col-start-11">
                            <p class="text-sm font-semibold">{{ __('site.suggestions') }}</p>
                            <ul class="mt-4 grid gap-1 border-t border-line-strong pt-3" data-suggestions>
                                @foreach ($suggestions as $suggestion)
                                    <li data-suggestion="{{ $suggestion->id }}">
                                        <a href="{{ $suggestion->template->name === 'promo' ? ($suggestion->link_page?->url() ?? $suggestion->link_url) : $suggestion->url() }}" class="block py-1.5 text-[0.9375rem] text-ink-soft hover:text-ink hover:underline">{{ $suggestion->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endLynxCache
            </div>
        </div>
    </details>
@endif
