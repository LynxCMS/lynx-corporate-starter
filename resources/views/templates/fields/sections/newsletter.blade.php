{{--
    Signing up for the newsletter, beside the latest news headlines. The headlines are kept as a
    fragment until content changes; the form loads once it scrolls into view, so neither costs the
    page's render. Its box keeps the form's height meanwhile, so the page doesn't jump.
--}}
<x-band :block="$block" type="newsletter">
    <div class="grid gap-12 lg:grid-cols-16">
        <div class="bg-mist p-8 lg:col-span-7">
            <h2 class="text-title font-light">{{ $block->heading }}</h2>
            @if ($block->intro)
                <p class="mt-4 text-lg text-ink-soft">{{ $block->intro }}</p>
            @endif
            <div class="mt-8 min-h-[24.5rem] xl:min-h-[23rem]" data-newsletter-form>
                <x-lynx-form :form="$block->form" lazy />
            </div>
        </div>
        @lynxCache('section-newsletter-news', ['vary' => [$block->key]])
        @php($news = \App\Support\LatestPages::find('news', size: $block->list_size))
        @if ($news->count())
            <div class="lg:col-span-7 lg:col-start-10">
                <h3 class="text-xl">{{ __('site.latest') }}</h3>
                <ul class="mt-6 border-t border-line" data-latest-news>
                    @foreach ($news as $item)
                        <li class="border-b border-line">
                            <a href="{{ $item->url() }}" class="group block py-4">
                                <span class="eyebrow block">{{ $item->published_on?->locale(app()->getLocale())->isoFormat('LL') }}</span>
                                <span class="mt-1 block text-lg group-hover:text-signal group-hover:underline">{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @endLynxCache
    </div>
</x-band>
