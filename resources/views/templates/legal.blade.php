{{-- A page of plain text, such as the privacy notice: a title, a lead and headed paragraphs. --}}
<x-layout :page="$page">
    <article class="shell py-16">
        <h1 class="text-display font-light">{{ $page->title }}</h1>
        @if ($page->summary)
            <p class="mt-6 max-w-3xl text-lg text-ink-soft">{{ $page->summary }}</p>
        @endif
        <div class="mt-12 max-w-3xl text-lg leading-relaxed [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-light [&_h2+p]:mt-3 [&_p+p]:mt-4" data-legal-body>
            @field($page->body)
        </div>
    </article>
</x-layout>
