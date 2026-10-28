{{-- One featured page with its picture, beside a list of links to other pages. --}}
@php($links = $block->links->publishedIn())
<x-band :block="$block" type="feature-links" :heading="$block->heading" :intro="$block->intro">
    <div class="grid gap-10 lg:grid-cols-16">
        @if ($block->feature)
            <div class="lg:col-span-8">
                <x-card :page="$block->feature" tone="gray" />
            </div>
        @endif
        @if ($links->count())
            <div class="lg:col-span-7 lg:col-start-10">
                <ul class="border-t border-line" data-links>
                    @foreach ($links as $link)
                        <li class="border-b border-line">
                            <a href="{{ $link->url() }}" class="group flex items-center justify-between gap-6 py-4 text-lg hover:text-signal">
                                {{ $link->title }}
                                <svg viewBox="0 0 20 20" class="size-5 shrink-0 fill-none stroke-signal stroke-[1.5] transition-transform group-hover:translate-x-1" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-band>
