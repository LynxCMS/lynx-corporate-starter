{{-- The mega-menu on phones: each section opens in place, its columns stacked. --}}
<nav aria-label="{{ $menu->title }}" {{ $attributes }} data-menu="top-mobile">
    <ul class="divide-y divide-line">
        @foreach ($menu->items as $item)
            <li>
                <details name="mega-mobile" class="group/section">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-4 [&::-webkit-details-marker]:hidden">
                        {{ $item->label }}
                        <svg viewBox="0 0 16 16" class="size-4 transition-transform group-open/section:rotate-180" aria-hidden="true"><path d="M4 6l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                    </summary>
                    <div class="grid gap-6 bg-mist px-4 py-6">
                        @foreach ($item->children as $column)
                            <div>
                                <a href="{{ $column->url }}" class="text-sm font-semibold">{{ $column->label }}</a>
                                <ul class="mt-2 grid">
                                    @foreach ($column->children as $link)
                                        <li><a href="{{ $link->url }}" class="block py-2 text-ink-soft" @if ($link->isActive) aria-current="page" @endif>{{ $link->label }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </details>
            </li>
        @endforeach
    </ul>
</nav>
