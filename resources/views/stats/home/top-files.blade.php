{{-- The admin home's list of the most downloaded files in the last 30 days (App\Stats\Admin\TopLists). --}}
@php($rows = app(\App\Stats\Admin\TopLists::class)->files())
<x-lynx-admin::home.section id="home-stats-files" data-home-panel="stats-files" icon="download"
    :title="__('stats.most_downloaded')"
    :is-empty="$rows === []" empty-icon="download"
    :empty-title="__('stats.no_downloads')" :empty-help="__('stats.empty_help')">
    @foreach ($rows as $row)
        <x-lynx-admin::home.row :url="$row['url']" :title="$row['title']" data-stats-row="{{ $row['id'] }}" data-stats-total="{{ $row['total'] }}">
            @if ($row['subject'] !== '')
                <span class="text-foreground text-xs font-medium uppercase" data-stats-subject="{{ $row['subject'] }}">{{ $row['subject'] }}</span>
            @endif
            {{-- Its share of the first row, as a bar (BlatUI's meter, drawn with the admin's own classes). --}}
            <span class="bg-muted mt-1 block h-1.5 w-full overflow-hidden rounded-full" aria-hidden="true">
                <span class="bg-primary block h-full rounded-full" style="width: {{ $row['share'] }}%"></span>
            </span>
            <x-slot:aside>{{ \Illuminate\Support\Number::format($row['total'], locale: app()->getLocale()) }}</x-slot:aside>
        </x-lynx-admin::home.row>
    @endforeach
</x-lynx-admin::home.section>
