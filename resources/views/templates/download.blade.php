{{--
    A report or ebook: what it is on the left, the download form on the right. The page is stored
    by the page cache; the form is an island, rendered for each visitor, so its token and spam
    check stay fresh.
--}}
@php($privacy = $page->parent()?->privacy_page)
<x-layout :page="$page">
    <article class="shell grid gap-12 py-16 lg:grid-cols-16">
        <div class="lg:col-span-9">
            <a href="{{ $page->parent()?->url() }}" class="eyebrow hover:text-signal">{{ $page->download_kind?->label() }}</a>
            <h1 class="mt-4 text-display font-light">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-6 text-lg text-ink-soft">{{ $page->summary }}</p>
            @endif
            @if ($page->image)
                <x-lynx-image :image="$page->image" :width="1200" :height="675" sizes="(min-width: 1024px) 55vw, 100vw" :lazy="false" class="mt-10 w-full" alt="" />
            @endif
            <div class="mt-10 max-w-3xl text-lg leading-relaxed [&_p+p]:mt-4">@field($page->body)</div>
        </div>
        <aside class="lg:col-span-6 lg:col-start-11">
            <div class="border border-line bg-canvas p-6 shadow-sm lg:sticky lg:top-20" data-download-card>
                <p class="eyebrow text-signal">{{ __('site.download.'.($page->download_kind?->value ?? 'report')) }}</p>
                <h2 class="mt-2 text-xl font-semibold">{{ $page->title }}</h2>
                <div class="mt-6" data-download-form>
                    <x-lynx-island livewire="download-form" :with="['form' => \App\Livewire\DownloadForm::FORM, 'pageId' => $page->id]">
                        <p class="text-ink-soft">{{ __('site.download_loading') }}</p>
                    </x-lynx-island>
                </div>
                @if ($privacy)
                    <p class="mt-6 text-sm italic text-ink-soft" data-privacy-notice>
                        {!! __('site.download_privacy', ['link' => '<a href="'.e($privacy->url()).'" class="underline hover:text-signal">'.e(__('site.download_privacy_link')).'</a>']) !!}
                    </p>
                @endif
            </div>
        </aside>
    </article>
</x-layout>
