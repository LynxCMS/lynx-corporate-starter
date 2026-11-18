{{-- resources/views/templates/photo.blade.php --}}
{{-- A photo album: its title, description and photo count, then the Flickr thumbnails that App\Flickr\FillFromFlickr keeps in the page. Each opens the photo's page on flickr.com. --}}
@php
    $thumbnails = array_values(array_filter(explode("\n", (string) $page->thumbnails)));
    $photoCount = (int) (string) $page->photo_count;
    $albumId = (string) $page->flickr_album_id;
@endphp
<x-layout :page="$page">
    <div class="shell py-16">
        <h1 class="text-display font-light">{{ $page->title }}</h1>
        @if ($page->flickr_description)
            <p class="mt-6 max-w-2xl text-lg text-ink-soft">{{ $page->flickr_description }}</p>
        @endif
        <p class="mt-4 text-sm text-ink-soft">{{ trans_choice(':count photo|:count photos', $photoCount) }}</p>
        <ul class="mt-10 grid grid-cols-3 gap-4 sm:grid-cols-5">
            @foreach ($thumbnails as $index => $image)
                @php($photoPage = \App\Flickr\PhotoPage::url($image, $albumId))
                <li>
                    @if ($photoPage)
                        <a href="{{ $photoPage }}" target="_blank" rel="noopener" class="block focus-visible:outline-2 focus-visible:outline-offset-2"
                           aria-label="{{ __('site.open_on_flickr', ['number' => $index + 1, 'count' => count($thumbnails)]) }}">
                            <img src="{{ $image }}" alt="" width="150" height="150" loading="lazy" class="w-full rounded">
                        </a>
                    @else
                        <img src="{{ $image }}" alt="" width="150" height="150" loading="lazy" class="w-full rounded">
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</x-layout>
