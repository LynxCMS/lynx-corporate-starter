{{-- A topic: what it covers, then the pages tagged with it, grouped by type (App\Support\TopicPages). --}}
@php($groups = \App\Support\TopicPages::for($page))
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
    <div class="shell grid gap-16 py-16">
        @forelse ($groups as $group)
            <section data-topic-group="{{ $group['type'] }}" aria-labelledby="topic-{{ $group['type'] }}">
                <div class="flex flex-wrap items-baseline justify-between gap-4">
                    <h2 id="topic-{{ $group['type'] }}" class="text-title font-light">{{ $group['list']?->title }}</h2>
                    @if ($group['list'])
                        <a href="{{ $group['list']->url() }}" class="link-arrow text-sm">{{ __('site.view_all') }}</a>
                    @endif
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($group['pages'] as $item)
                        <div class="ring-1 ring-line"><x-card :page="$item" /></div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-lg text-ink-soft" data-topic-empty>{{ __('site.topic_empty') }}</p>
        @endforelse
    </div>
</x-layout>
