@props(['page', 'downloads' => null])
{{--
    A download an editor features on the resources page. Its picture leads as a thumbnail; one
    without a picture gets a cover in the site's night blue that names its kind, so the row keeps
    one shape. On phones the thumbnail sits small beside the text, so four of them stay short.
--}}
@php($kind = $page->download_kind?->label())
<article class="group relative flex h-full border-t-2 border-signal bg-mist transition-colors hover:bg-line sm:flex-col" data-featured-item="{{ $page->id }}">
    @if ($page->image)
        <x-lynx-image :image="$page->image" :width="800" :height="600" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 7rem" :lazy="false" class="aspect-square w-28 shrink-0 object-cover sm:aspect-[4/3] sm:w-full" alt="" data-featured-thumb="{{ $page->id }}" />
    @else
        <div class="on-night flex aspect-square w-28 shrink-0 flex-col items-start justify-end bg-night p-4 text-white sm:aspect-[4/3] sm:w-full sm:justify-between sm:p-6" data-featured-cover="{{ $page->id }}" aria-hidden="true">
            @if ($kind)
                <p class="eyebrow hidden sm:block">{{ $kind }}</p>
            @endif
            <svg viewBox="0 0 24 32" class="size-8 fill-none stroke-signal-soft stroke-[1.25] sm:size-12"><path d="M2 1h14l6 6v24H2z M16 1v6h6 M6 15h12 M6 20h12 M6 25h8"/></svg>
        </div>
    @endif
    <div class="flex min-w-0 flex-1 flex-col gap-2 p-4 pb-12 sm:gap-3 sm:p-6 sm:pb-14">
        @if ($kind)
            <p @class(['eyebrow', 'sm:hidden' => ! $page->image])>{{ $kind }}</p>
        @endif
        <h3 class="text-lg leading-snug sm:text-xl">
            <a href="{{ $page->url() }}" class="after:absolute after:inset-0 group-hover:underline">{{ $page->title }}</a>
        </h3>
        @if ($page->summary)
            <p class="hidden text-[0.9375rem] text-ink-soft sm:line-clamp-3">{{ $page->summary }}</p>
        @endif
        <x-download-meta :page="$page" :downloads="$downloads" />
    </div>
    <svg viewBox="0 0 20 20" class="absolute bottom-4 left-32 size-5 fill-none stroke-signal stroke-[1.5] transition-transform group-hover:translate-x-1 sm:bottom-5 sm:left-6" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
</article>
