<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Downloads\Document;
use App\Downloads\NewsletterSignup;
use App\Livewire\DownloadForm;
use App\Mail\DownloadLinkMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use LynxCms\Forms\Events\FormSubmitted;
use LynxCms\Forms\Models\Submission;

/**
 * After a download request: the link to the file by email, in the language the visitor used, and
 * a newsletter subscription when they ticked the box (once per address). Queued, so neither
 * can get in the way of the visitor's download.
 */
final class SendDownloadLink implements ShouldQueue
{
    public function handle(FormSubmitted $event): void
    {
        $submission = Submission::query()->find($event->submissionId);

        if (! $submission instanceof Submission || $submission->form !== DownloadForm::FORM) {
            return;
        }

        $language = (string) ($submission->language()->code ?? lynx()->languages->default()->code);
        lynx()->language($language, fn () => $this->mail($submission));

        if ((bool) ($submission->data['newsletter'] ?? false)) {
            (new NewsletterSignup)->subscribe($submission->data, $language);
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
}
