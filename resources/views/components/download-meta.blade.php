@props(['page', 'downloads' => null])
{{--
    A download's languages and, when the page passes it (App\Stats\PageStats), its download count:
    the last line of its card and of its featured card.
--}}
@php($languages = (new \App\Downloads\Document($page))->languages())
<p {{ $attributes->class(['mt-auto pt-4 text-sm text-ink-soft']) }} data-languages="{{ implode(' ', $languages) }}">
    {{ count($languages) === 1 ? __('site.only_language', ['language' => __('site.language_names.'.$languages[0])]) : strtoupper(implode(' · ', $languages)) }}
    @if ($downloads !== null)
        <span class="block" data-downloads="{{ $downloads }}">{{ trans_choice('stats.downloads', $downloads, ['count' => \Illuminate\Support\Number::withLocale(app()->getLocale(), fn () => \Illuminate\Support\Number::abbreviate($downloads, maxPrecision: 1))]) }}</span>
    @endif
</p>
