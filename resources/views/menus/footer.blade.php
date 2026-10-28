{{-- The footer: one column per section label, the pages under it, and a line for the company. --}}
<footer class="bg-night text-white" data-menu="footer">
    <div class="shell grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <p class="text-lg font-semibold">Arcturus Systems</p>
        @foreach ($menu->items as $column)
            <div>
                <p class="text-sm text-white/60">{{ $column->label }}</p>
                <ul class="mt-4 grid gap-2">
                    @foreach ($column->children as $link)
                        <li><a href="{{ $link->url }}" class="text-[0.9375rem] text-white/85 hover:text-white hover:underline">{{ $link->label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="border-t border-night-line">
        <p class="shell py-5 text-sm text-white/60">© {{ now()->year }} Arcturus Systems. @string('footer.note', ['en' => 'An invented company, for a Lynx demo.', 'pt' => 'Uma empresa inventada, para uma demonstração do Lynx.'], label: 'Footer: note')</p>
    </div>
</footer>
