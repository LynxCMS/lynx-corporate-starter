@props(['page'])
{{-- The page in the other language: EN / PT, the current one marked. --}}
@if (lynx()->languages->isMultilingual())
    <div {{ $attributes->merge(['class' => 'flex items-center text-sm']) }} role="group" aria-label="{{ __('site.language') }}">
        @foreach (lynx()->languages->switcher($page) as $language)
            <a href="{{ $language['url'] }}" hreflang="{{ $language['language']->appLocale() }}" lang="{{ $language['language']->appLocale() }}"
               @if ($language['isCurrent']) aria-current="true" @endif
               @class(['px-2 py-1 uppercase', 'font-semibold text-ink' => $language['isCurrent'], 'text-ink-soft hover:text-signal' => ! $language['isCurrent']])>{{ $language['language']->code }}</a>
        @endforeach
    </div>
@endif
