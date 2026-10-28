{{--
    The hero carousel: every published slide of the chosen branch (pages without a URL, in tree
    order), all in the HTML so the page cache keeps them and the hero works without JavaScript.
    resources/js/carousel.js adds the buttons, the dots and turning by itself.
--}}
@php($slides = $block->slides_from?->children('template=hero-slide') ?? [])
@if (count($slides))
    <section class="relative bg-mist" data-section="hero" data-carousel @if ($block->autoplay) data-autoplay="7000" @endif
             aria-roledescription="carousel" aria-label="{{ $block->heading ?: __('site.slides') }}">
        <div class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none]" data-carousel-track>
            @foreach ($slides as $slide)
                <article class="grid w-full shrink-0 snap-start lg:grid-cols-2" data-carousel-slide data-slide="{{ $slide->name }}"
                         aria-roledescription="slide" aria-label="{{ __('site.slide', ['number' => $loop->iteration, 'total' => count($slides)]) }}">
                    <div class="shell flex flex-col justify-center gap-6 py-14 lg:py-20 lg:pr-16 lg:pl-[max(3rem,calc((100vw-99rem)/2+3rem))]">
                        @if ($slide->eyebrow)
                            <p class="eyebrow">{{ $slide->eyebrow }}</p>
                        @endif
                        <h2 class="text-display font-light">{{ $slide->title }}</h2>
                        @if ($slide->summary)
                            <p class="max-w-xl text-lg text-ink-soft">{{ $slide->summary }}</p>
                        @endif
                        @if ($href = $slide->link_page?->url() ?? $slide->link_url)
                            <a href="{{ $href }}" class="button self-start">{{ $slide->cta_label ?: __('site.more') }}
                                <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
                            </a>
                        @endif
                    </div>
                    @if ($slide->image)
                        <x-lynx-image :image="$slide->image" :width="1600" :height="900" sizes="(min-width: 1024px) 50vw, 100vw" :lazy="! $loop->first" class="h-full max-h-[36rem] w-full object-cover" alt="" />
                    @endif
                </article>
            @endforeach
        </div>
        <div class="shell flex items-center gap-4 pb-8 lg:absolute lg:bottom-0 lg:left-1/2 lg:-translate-x-1/2" data-carousel-controls hidden>
            <button type="button" class="grid size-10 place-items-center bg-canvas hover:bg-line" data-carousel-prev aria-label="{{ __('site.previous') }}">
                <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M17 10H4M9 5l-5 5 5 5"/></svg>
            </button>
            <div class="flex gap-2">
                @foreach ($slides as $slide)
                    <button type="button" class="h-1 w-8 bg-line-strong aria-[current=true]:bg-signal" data-carousel-dot="{{ $loop->index }}"
                            aria-label="{{ __('site.slide', ['number' => $loop->iteration, 'total' => count($slides)]) }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
            <button type="button" class="grid size-10 place-items-center bg-canvas hover:bg-line" data-carousel-next aria-label="{{ __('site.next') }}">
                <svg viewBox="0 0 20 20" class="size-5 fill-none stroke-current stroke-[1.5]" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
            </button>
        </div>
    </section>
@endif
