{{-- The newest news or insights, optionally on one topic: a list that fills itself. --}}
@php($pages = \App\Support\LatestPages::find($block->source?->value ?? 'news', $block->topic, $block->list_size))
@php($theme = $block->theme?->value ?? 'white')
@if ($pages->count())
    <x-band :block="$block" type="latest" :heading="$block->heading">
        <div class="grid gap-px sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pages as $item)
                <x-card :page="$item" :tone="['white' => 'gray', 'gray' => 'light', 'dark' => 'dark'][$theme]" />
            @endforeach
        </div>
    </x-band>
@endif
