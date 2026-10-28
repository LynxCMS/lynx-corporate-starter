{{--
    The mega-menu. Each top item is a section label that opens a panel; the pages under it are the
    panel's columns, each listing its own child pages. Panels open with <details>, so the menu needs
    no JavaScript, and name="mega" lets only one stay open.
--}}
<nav aria-label="{{ $menu->title }}" {{ $attributes }} data-menu="top">
    <ul class="flex h-14 items-stretch">
        @foreach ($menu->items as $item)
            <li class="flex">
                <details name="mega" class="group flex">
                    <summary @class([
                        'flex cursor-pointer list-none items-center gap-1 border-b-2 px-4 text-[0.9375rem] [&::-webkit-details-marker]:hidden',
                        'border-signal' => $item->isInActiveTrail,
                        'border-transparent hover:bg-mist group-open:border-signal group-open:bg-mist' => ! $item->isInActiveTrail,
                    ])>
                        {{ $item->label }}
                        <svg viewBox="0 0 16 16" class="size-4 transition-transform group-open:rotate-180" aria-hidden="true"><path d="M4 6l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                    </summary>
                    <div class="absolute inset-x-0 top-14 border-b border-line bg-mist shadow-[0_12px_24px_-16px_rgb(0_0_0/0.35)]" data-menu-panel>
                        <div class="shell grid gap-x-8 gap-y-10 py-10 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($item->children as $column)
                                <div>
                                    @if ($column->hasUrl())
                                        <a href="{{ $column->url }}" class="inline-flex items-center font-semibold text-signal hover:text-black" @if ($column->isActive) aria-current="page" @endif>
                                            {{ $column->label }}
                                            <svg viewBox="0 0 20 20" class="size-4 fill-none mt-0.5 ml-1 stroke-signal stroke-[1.5]" aria-hidden="true"><path d="M3 10h13M11 5l5 5-5 5"></path></svg>
                                        </a>
                                    @else
                                        <p class="text-sm font-semibold">{{ $column->label }}</p>
                                    @endif
                                    <ul class="mt-4 grid gap-1 border-t border-line-strong pt-3">
                                        @foreach ($column->children as $link)
                                            <li>
                                                <a href="{{ $link->url }}" @if ($link->isActive) aria-current="page" @endif
                                                   @class(['block py-1.5 text-[0.9375rem]', 'text-signal' => $link->isActive, 'text-ink-soft hover:text-ink hover:underline' => ! $link->isActive])>{{ $link->label }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            </li>
        @endforeach
    </ul>
</nav>
