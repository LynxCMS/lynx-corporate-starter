<x-mail::message>
{{ __('site.download_mail.intro', ['title' => $title, 'language' => $languageName]) }}

<x-mail::button :url="$url">
{{ __('site.download_mail.button') }}
</x-mail::button>

{{ __('site.download_mail.expires', ['days' => $days]) }}
</x-mail::message>
