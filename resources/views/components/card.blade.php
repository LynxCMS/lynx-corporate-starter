@props(['page', 'tone' => 'light', 'lazy' => true])
{{--
    One card, whatever the page's template: every section shows pages through it. The template
    decides the eyebrow (a kind, a client or a date), whether a picture or a figure leads, and where
    the card links: its own page, or for a promo, the page or address the promo points at.
--}}
@php
    $template = $page->template->name;
    $href = match ($template) {
        'promo', 'hero-slide' => $page->link_page?->url() ?? $page->link_url,
        default => $page->url(),
    };
    $eyebrow = match ($template) {
        'resource', 'promo' => $page->kind?->label(),
        'case-study' => $page->client,
        'news' => $page->published_on?->locale(app()->getLocale())->isoFormat('LL'),
        default => null,
    };
    $picture = $template === 'case-study' ? null : $page->image;
@endphp
<article @class([
    'group relative flex h-full flex-col transition-colors',
    'bg-canvas hover:bg-mist' => $tone === 'light',
    'bg-mist hover:bg-line' => $tone === 'gray',
    'bg-night-raised text-white hover:bg-night-line on-night' => $tone === 'dark',
]) data-card="{{ $template }}">
    @if ($picture)
        <x-lynx-image :image="$picture" :width="800" :height="450" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" :lazy="$lazy" class="aspect-video w-full object-cover" alt="" />
    @endif
    <div class="flex flex-1 flex-col gap-3 p-6 pb-14">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h3 class="text-xl leading-snug">
            @if ($href)
                <a href="{{ $href }}" class="after:absolute after:inset-0 group-hover:underline">{{ $page->title }}</a>
            @else
                {{ $page->title }}
            @endif
        </h3>
        @if ($template === 'product' && $page->claim)
            <p class="text-[0.9375rem]">{{ $page->claim }}</p>
        @endif
        @if ($page->summary)
            <p @class(['text-[0.9375rem]', 'text-ink-soft' => $tone !== 'dark', 'text-white/75' => $tone === 'dark'])>{{ $page->summary }}</p>
        @endif
        @if ($page->stat_value)
            <p class="mt-auto pt-6" data-card-stat>
                <span class="block text-4xl font-light text-signal">{{ $page->stat_value }}</span>
                <span class="text-sm">{{ $page->stat_label }}</span>
            </p>
        @endif
    </div>
    @if ($href)
        <svg viewBox="0 0 20 20" class="absolute bottom-5 left-6 size-5 fill-none stroke-signal stroke-[1.5] transition-transform group-hover:translate-x-1" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
    @endif
</article>
