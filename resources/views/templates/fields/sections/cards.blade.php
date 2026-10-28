{{--
    Pages an editor picked, as cards: in four or three columns, or a carousel. Cards sit on a tile a
    step away from the section's background, so they read as cards without borders.
--}}
@php
    $cards = \App\Support\Featured::inCurrentLanguage($block->cards);
    $layout = $block->layout?->value ?? 'grid-4';
    $theme = $block->theme?->value ?? 'white';
    $tone = ['white' => 'gray', 'gray' => 'light', 'dark' => 'dark'][$theme];
@endphp
@if ($cards->count())
    <x-band :block="$block" type="cards" :heading="$block->heading" :intro="$block->intro" data-layout="{{ $layout }}">
        @if ($layout === 'carousel')
            <div data-carousel>
                <div class="flex snap-x snap-mandatory gap-px overflow-x-auto pb-2 [scrollbar-width:thin]" data-carousel-track>
                    @foreach ($cards as $card)
                        <div class="w-[85%] shrink-0 snap-start sm:w-[calc(50%-1px)] lg:w-[calc(33.333%-1px)]" data-carousel-slide>
                            <x-card :page="$card" :tone="$tone" />
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex gap-2" data-carousel-controls hidden>
                    <button type="button" @class(['grid size-10 place-items-center', 'bg-night-raised hover:bg-night-line' => $theme === 'dark', 'bg-mist hover:bg-line' => $theme !== 'dark']) data-carousel-prev aria-label="{{ __('site.previous') }}">
                        <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M17 10H4M9 5l-5 5 5 5"/></svg>
                    </button>
                    <button type="button" @class(['grid size-10 place-items-center', 'bg-night-raised hover:bg-night-line' => $theme === 'dark', 'bg-mist hover:bg-line' => $theme !== 'dark']) data-carousel-next aria-label="{{ __('site.next') }}">
                        <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
                    </button>
                </div>
            </div>
        @else
            <div @class(['grid gap-px sm:grid-cols-2', 'lg:grid-cols-4' => $layout === 'grid-4', 'lg:grid-cols-3' => $layout === 'grid-3'])>
                @foreach ($cards as $card)
                    <x-card :page="$card" :tone="$tone" />
                @endforeach
            </div>
        @endif
    </x-band>
@endif
