{{-- A list page (products, case studies, insights, news, training, topics): its pages as cards. --}}
@php($sort = ['newsroom' => 'sort=-published_on', 'insights' => 'sort=-date'][$page->template->name] ?? 'sort=sort')
<x-layout :page="$page">
    <header class="bg-mist">
        <div class="shell py-16">
            <h1 class="text-display font-light">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->summary }}</p>
            @endif
        </div>
    </header>
    <div class="shell py-16">
        <div class="grid gap-px bg-line sm:grid-cols-2 lg:grid-cols-3" data-list>
            @foreach ($page->children($sort) as $child)
                <x-card :page="$child" />
            @endforeach
        </div>
    </div>
</x-layout>
