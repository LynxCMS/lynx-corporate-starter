<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Downloads\Document;
use App\Livewire\DownloadForm;
use App\Mail\DownloadLinkMail;
use Illuminate\Support\Facades\Mail;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use LynxCms\Forms\Actions\SubmitForm;
use LynxCms\Forms\Events\FormSubmitted;
use LynxCms\Forms\Models\Submission;

/**
 * After a download request: the link to the file by email, in the language the visitor used, and
 * a newsletter subscription when they ticked the box (once per address).
 */
final class SendDownloadLink
{
    private const string NEWSLETTER = 'newsletter';

    public function handle(FormSubmitted $event): void
    {
        $submission = Submission::query()->find($event->submissionId);

        if (! $submission instanceof Submission || $submission->form !== DownloadForm::FORM) {
            return;
        }

        $language = (string) ($submission->language()->code ?? lynx()->languages->default()->code);
        lynx()->language($language, fn () => $this->mail($submission));

        if ((bool) ($submission->data['newsletter'] ?? false)) {
            $this->subscribe(Value::string($submission->data['email'] ?? ''), $language);
        }
    }

    private function mail(Submission $submission): void
    {
        $page = $submission->page_id === null ? null : lynx()->pages->get($submission->page_id);
        $chosen = Value::string($submission->data[DownloadForm::LANGUAGE] ?? '');
        $file = $page instanceof Page ? (new Document($page))->file($chosen) : null;

        if ($file === null) {
            return;
        }

        $days = Value::int(config('downloads.email_link_days'));
        $mail = new DownloadLinkMail($file->temporaryUrl(now()->addDays($days)), (string) $page->title, __("site.language_names.{$chosen}"), $days);
        Mail::to(Value::string($submission->data['email'] ?? ''))->locale(app()->getLocale())->queue($mail);
    }

    private function subscribe(string $email, string $language): void
    {
        $isSubscribed = Submission::query()->where('form', self::NEWSLETTER)->where('data->email', $email)->exists();

        if (! $isSubscribed && $email !== '') {
            lynx()->run(SubmitForm::class, ['form' => self::NEWSLETTER, 'data' => ['email' => $email], 'language' => $language]);
        }
    }
}
