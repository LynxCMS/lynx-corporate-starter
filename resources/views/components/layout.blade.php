@props(['page'])
{{--
    The site's frame: the mega-menu, the language switcher, the page and the footer. Pages get their
    title, description, share tags, hreflang and structured data from Lynx SEO. Both menus are kept
    as page parts (lynxcms/cache), varied by page because they mark the current one.
--}}
@php($home = lynx()->pages->get('/'))
<!DOCTYPE html>
<html lang="{{ lynx()->languages->current()->appLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-lynx-seo :page="$page" />
    @lynxCsrf
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas font-sans text-ink">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-signal focus:px-4 focus:py-3 focus:text-white">{{ __('site.skip') }}</a>

    <header class="sticky top-0 z-40 border-b border-line bg-canvas">
        <div class="shell flex h-14 items-center gap-8">
            <a href="{{ $home?->url() }}" class="flex shrink-0 items-center gap-2 text-lg font-semibold tracking-tight" data-home-link>
                <svg viewBox="0 0 24 24" class="size-6 fill-signal" aria-hidden="true"><path d="M12 1.5 14.6 9.4 22.5 12l-7.9 2.6L12 22.5l-2.6-7.9L1.5 12l7.9-2.6Z"/></svg>
                Arcturus
            </a>
            @lynxCache('menu-top', ['vary' => [$page->id]])
                <x-lynx-menu name="top" view="menus.mega" class="hidden flex-1 lg:block" />
            @endLynxCache
            <x-language-switcher :page="$page" class="ml-auto" />
            <details class="group lg:hidden">
                <summary class="flex h-14 cursor-pointer list-none items-center px-2 text-[0.9375rem] [&::-webkit-details-marker]:hidden">{{ __('site.menu') }}</summary>
                <div class="absolute inset-x-0 top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto border-b border-line bg-canvas">
                    @lynxCache('menu-top-mobile', ['vary' => [$page->id]])
                        <x-lynx-menu name="top" view="menus.mobile" />
                    @endLynxCache
                </div>
            </details>
        </div>
    </header>

    <main id="content" class="flex-1">
        {{ $slot }}
    </main>

    @lynxCache('menu-footer', ['vary' => [$page->id]])
        <x-lynx-menu name="footer" view="menus.footer" />
    @endLynxCache
</body>
</html>
