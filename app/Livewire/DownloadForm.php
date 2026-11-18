<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Downloads\Document;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use LynxCms\Forms\Definition\FormField;
use LynxCms\Forms\Livewire\FormComponent;
use LynxCms\Forms\Models\Submission;

/**
 * The "download" form beside a download page: the language choice offers the languages the
 * document exists in, starting with the visitor's, and is left out when there is only one. Once
 * the lead is stored, the visitor gets a temporary link to the file and the download starts.
 */
final class DownloadForm extends FormComponent
{
    /** The builder form's name (DownloadsSchemaSeeder makes it). */
    public const string FORM = 'download';

    public const string LANGUAGE = 'document_language';

    #[Locked]
    public ?string $downloadUrl = null;

    public function mount(string $form): void
    {
        parent::mount($form);
        $current = $this->lang ?? (string) lynx()->languages->current()->code;
        $language = $this->document()?->defaultLanguage($current);

        if ($language !== null) {
            $this->data[self::LANGUAGE] = $language;
        }
    }

    /** @return list<FormField> */
    public function visibleFields(): array
    {
        $isChoice = count($this->languages()) > 1;

        return array_values(array_filter(parent::visibleFields(), fn (FormField $field): bool => $isChoice || $field->name !== self::LANGUAGE));
    }

    public function render(): View
    {
        $language = Value::string($this->data[self::LANGUAGE] ?? '');

        return view('livewire.download-form', [
            ...parent::render()->getData(),
            'languages' => $this->languages(),
            'languageName' => $language === '' ? '' : __("site.language_names.{$language}"),
        ]);
    }

    protected function fieldOptions(FormField $field): array
    {
        return $field->name === self::LANGUAGE ? array_intersect_key($field->options, array_flip($this->languages())) : $field->options;
    }

    protected function afterSent(Submission $submission): void
    {
        $file = $this->document()?->file(Value::string($this->data[self::LANGUAGE] ?? ''));

        if ($file === null) {
            return;
        }

        $this->downloadUrl = $file->temporaryUrl(now()->addHours(Value::int(config('downloads.page_link_hours'))));
        $this->dispatch('download-ready', url: $this->downloadUrl);
    }

    /** @return list<string> */
    private function languages(): array
    {
        return $this->document()?->languages() ?? [];
    }

    private function document(): ?Document
    {
        $page = $this->page();

        return $page instanceof Page ? new Document($page) : null;
    }
}
