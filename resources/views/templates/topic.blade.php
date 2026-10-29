{{-- A topic: what it covers, then the pages tagged with it, grouped by type (App\Support\TopicPages). --}}
@php
    $groups = \App\Support\TopicPages::for($page);
@endphp
<x-layout :page="$page">
    <header class="bg-mist">
        <div class="shell py-16">
            <a href="{{ $page->parent()?->url() }}" class="eyebrow hover:text-signal">{{ $page->parent()?->title }}</a>
            <h1 class="mt-4 text-display font-light">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->summary }}</p>
            @endif
        </div>
    </header>
    <div class="shell py-16">
        <div class="grid max-w-5xl gap-16">
        @forelse ($groups as $group)
            <section data-topic-group="{{ $group['type'] }}" aria-labelledby="topic-{{ $group['type'] }}">
                <div class="flex flex-wrap items-baseline justify-between gap-4">
                    <h2 id="topic-{{ $group['type'] }}" class="text-title font-light">{{ $group['list']?->title }}</h2>
                    @if ($group['list'])
                        <a href="{{ $group['list']->url() }}" class="link-arrow text-sm">{{ __('site.view_all') }}</a>
                    @endif
                </div>
                <ul class="mt-6 border-t border-line">
                    @foreach ($group['pages'] as $item)
                        @php
                            // A line above the title: what the product promises, or what and when an insight or news item is.
                            $date = ($item->date ?? $item->published_on ?? null)?->locale(app()->getLocale())->isoFormat('LL');
                            $meta = match ($group['type']) {
                                'product' => $item->claim,
                                'resource' => implode(' · ', array_filter([$item->kind?->label(), $date])),
                                default => $date,
                            };
                        @endphp
                        <li class="border-b border-line">
                            <a href="{{ $item->url() }}" class="group grid gap-1 py-5 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-x-8" data-topic-item>
                                <span class="grid gap-1">
                                    @if ($meta)
                                        <span class="eyebrow">{{ $meta }}</span>
                                    @endif
                                    <span class="text-xl group-hover:text-signal group-hover:underline">{{ $item->title }}</span>
                                    @if ($item->summary)
                                        <span class="max-w-3xl text-[0.9375rem] text-ink-soft">{{ $item->summary }}</span>
                                    @endif
                                </span>
                                <svg viewBox="0 0 20 20" class="hidden size-5 fill-none stroke-signal stroke-[1.5] transition-transform group-hover:translate-x-1 sm:block" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="text-lg text-ink-soft" data-topic-empty>{{ __('site.topic_empty') }}</p>
        @endforelse
        </div>
    </div>
</x-layout>
