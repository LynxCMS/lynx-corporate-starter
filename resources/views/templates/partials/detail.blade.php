{{--
    A page a card links to: what it is, its picture and figure, its text, and the pages it names.
    The demo keeps these plain; the home page is the point.
--}}
<x-layout :page="$page">
    <article>
        <header class="bg-mist">
            <div class="shell grid gap-10 py-16 lg:grid-cols-16">
                <div class="lg:col-span-8">
                    <a href="{{ $page->parent()?->url() }}" class="eyebrow hover:text-signal">{{ $page->parent()?->title }}</a>
                    <h1 class="mt-4 text-display font-light">{{ $page->title }}</h1>
                    @if ($page->summary)
                        <p class="mt-6 text-lg text-ink-soft">{{ $page->summary }}</p>
                    @endif
                    @if ($page->stat_value ?? null)
                        <p class="mt-10"><span class="block text-5xl font-light text-signal">{{ $page->stat_value }}</span>{{ $page->stat_label }}</p>
                    @endif
                </div>
                @if ($page->image ?? null)
                    <x-lynx-image :image="$page->image" :width="1200" :height="675" sizes="(min-width: 1024px) 50vw, 100vw" :lazy="false" class="w-full lg:col-span-7 lg:col-start-10" alt="" />
                @endif
            </div>
        </header>
        <div class="shell py-16">
            <div class="max-w-3xl text-lg leading-relaxed [&_p+p]:mt-4">@field($page->body ?? null)</div>
            @foreach (['products', 'topics'] as $related)
                @if (($page->{$related} ?? null)?->count())
                    <ul class="mt-12 flex flex-wrap gap-2">
                        @foreach ($page->{$related} as $link)
                            <li><a href="{{ $link->url() }}" class="inline-block bg-mist px-3 py-1.5 text-sm hover:bg-line">{{ $link->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </div>
    </article>
</x-layout>
