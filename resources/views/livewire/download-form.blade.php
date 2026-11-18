{{--
    The download form (App\Livewire\DownloadForm), built from the "download" builder form's fields.
    A visitor who sent it before is remembered in this browser only: their details fill in, and
    the next download takes one click. Nothing about them is in the stored page.
--}}
@php($contact = ['first_name', 'last_name', 'email', 'job_title', 'job_title_other', 'company', 'company_size'])
<div class="lynx-form" data-form="{{ $definition->key() }}"
     x-data="downloadContact(@js($contact))"
     x-on:lynx-form-sent.window="remember()"
     x-on:download-ready.window="start($event.detail.url)">
    @if ($languages === [])
        <p class="text-ink-soft" role="status">{{ __('site.download_unavailable') }}</p>
    @elseif ($isSent)
        <div class="lynx-form-sent" role="status">
            <p>{{ $definition->text('success')->in() }}</p>
            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}" class="lynx-form-button mt-4 inline-block" data-download-link>{{ __('site.download_again') }}</a>
            @endif
        </div>
    @else
        <form x-on:submit.prevent="send()" class="lynx-form-body" novalidate>
            <x-honeypot livewire-model="extraFields" />

            @if ($errors->isNotEmpty())
                <p class="lynx-form-problem" role="alert" x-init="known = false">{{ __('lynx-forms::forms.check_fields') }}</p>
            @endif
            @if ($problem)
                <p class="lynx-form-problem" role="alert">{{ $problem }}</p>
            @endif

            {{-- The document's language first, as the visitor chooses what to download before saying who they are. --}}
            @if (count($languages) === 1)
                <p class="text-sm text-ink-soft" data-download-language>{{ __('site.download_available_in', ['language' => $languageName]) }}</p>
            @endif
            @foreach ($fields as $field)
                @if ($field->name === \App\Livewire\DownloadForm::LANGUAGE)
                    <div class="lynx-form-fields">@include('lynx-forms::fields.field', ['field' => $field, 'path' => 'data.'.$field->name])</div>
                @endif
            @endforeach

            <p class="text-ink-soft" x-show="! known">{{ __('site.download_intro') }}</p>
            <p class="text-ink-soft" x-show="known" x-cloak>
                <span x-text="@js(__('site.download_as', ['name' => '__NAME__'])).replace('__NAME__', name)"></span>
                <button type="button" class="underline hover:text-signal" x-on:click="forget()">{{ __('site.download_not_you') }}</button>
            </p>

            <div class="lynx-form-fields">
                @foreach ($fields as $field)
                    @if ($field->name === \App\Livewire\DownloadForm::LANGUAGE)
                        @continue
                    @elseif (in_array($field->name, $contact, true))
                        <div class="contents" x-show="! known">
                            @include('lynx-forms::fields.field', ['field' => $field, 'path' => 'data.'.$field->name])
                        </div>
                    @else
                        @include('lynx-forms::fields.field', ['field' => $field, 'path' => 'data.'.$field->name])
                    @endif
                @endforeach
            </div>

            <div class="lynx-form-actions">
                <button type="submit" class="lynx-form-button w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">{{ __('site.download_submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('lynx-forms::forms.sending') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
