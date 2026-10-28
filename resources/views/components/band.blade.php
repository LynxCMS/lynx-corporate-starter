@props(['block', 'heading' => null, 'intro' => null, 'type'])
{{--
    A section of the home page: its background (the section's theme), its heading and introduction,
    the content, and the section's button. data-section names the type, for tests and styling.
--}}
@php
    // Section types without a theme or a button leave those fields out of their blocks.
    $theme = ($block->theme ?? null)?->value ?? 'white';
    $cta = $block->cta_page ?? null;
    $ctaLabel = $block->cta_label ?? null;
@endphp
<section @class([
    'py-16 sm:py-20',
    'bg-canvas' => $theme === 'white',
    'bg-mist' => $theme === 'gray',
    'bg-night text-white on-night' => $theme === 'dark',
]) data-section="{{ $type }}" {{ $attributes }}>
    <div class="shell">
        @if ($heading || $intro)
            <div class="grid gap-4 lg:grid-cols-16">
                @if ($heading)
                    <h2 class="text-title font-light lg:col-span-7">{{ $heading }}</h2>
                @endif
                @if ($intro)
                    <p @class(['text-lg lg:col-span-6 lg:col-start-9', 'text-ink-soft' => $theme !== 'dark', 'text-white/75' => $theme === 'dark'])>{{ $intro }}</p>
                @endif
            </div>
        @endif
        <div @class(['mt-10' => $heading || $intro])>{{ $slot }}</div>
        @if ($cta && $ctaLabel)
            <a href="{{ $cta->url() }}" class="button mt-10" data-section-cta>{{ $ctaLabel }}
                <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
            </a>
        @endif
    </div>
</section>
