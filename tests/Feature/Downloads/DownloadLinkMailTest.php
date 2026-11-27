<?php

declare(strict_types=1);

use App\Exceptions\NewsletterSignupFailed;
use App\Listeners\SendDownloadLink;
use App\Mail\DownloadLinkMail;
use Database\Seeders\Support\Downloads;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use LynxCms\Forms\Actions\SubmitForm;
use LynxCms\Forms\FormSchema;
use LynxCms\Forms\Models\Submission;

/*
 * After a download request is stored, the visitor gets the link by email too, in the language
 * they used, and joins the newsletter when they ticked the box.
 */

beforeEach(function (): void {
    spanish();
    Mail::fake();
    $this->report = Downloads::create('Cloud outlook', 'report', ['en', 'pt', 'es']);
});

/** A download request as the form stores it. */
function requestDownload(array $answers = [], string $language = 'pt'): void
{
    lynx()->language($language, fn () => lynx()->run(SubmitForm::class, [
        'form' => 'download',
        'page' => test()->report->id,
        'language' => $language,
        'data' => $answers + ['document_language' => 'es', 'first_name' => 'Ana', 'last_name' => 'Lima', 'email' => 'ana@example.com', 'job_title' => 'manager'],
    ]));
}

it('emails the link to the file in the language chosen, in the visitor\'s language', function (): void {
    requestDownload();

    Mail::assertQueued(DownloadLinkMail::class, fn (DownloadLinkMail $mail): bool => $mail->hasTo('ana@example.com')
        && $mail->locale === 'pt-PT'
        && str_contains($mail->url, '/stats/file/'.test()->report->id.'/document/cloud-outlook-es'));
    Mail::assertQueuedCount(1);
});

it('sends nothing for other forms', function (): void {
    lynx()->run(SubmitForm::class, ['form' => 'newsletter', 'data' => ['email' => 'ana@example.com']]);

    Mail::assertNotQueued(DownloadLinkMail::class);
});

it('subscribes the visitor to the newsletter when they ask', function (): void {
    requestDownload(['newsletter' => true]);

    expect(Submission::query()->where('form', 'newsletter')->sole()->data['email'])->toBe('ana@example.com');
});

it('leaves the newsletter alone when the box is not ticked', function (): void {
    requestDownload();

    expect(Submission::query()->where('form', 'newsletter')->count())->toBe(0);
});

it('subscribes an address once', function (): void {
    requestDownload(['newsletter' => true]);
    requestDownload(['newsletter' => true]);

    expect(Submission::query()->where('form', 'newsletter')->count())->toBe(1);
});

/** Changes the newsletter form as an editor would in the builder. */
function newsletterAsks(array $fields): void
{
    $form = lynx()->pages->findOne('template=form, name=newsletter, include=all');
    $form->{FormSchema::FIELDS} = array_map(fn (array $field): array => ['type' => $field[0], 'values' => [
        FormSchema::LABEL => ['en' => $field[1]], FormSchema::NAME => $field[1], FormSchema::REQUIRED => $field[2],
    ]], $fields);
    $form->save();
}

it('fills the newsletter\'s other questions from the download request, and ticks its consent', function (): void {
    newsletterAsks([['text', 'first_name', true], ['text', 'last_name', true], ['email', 'email', true], ['checkbox', 'consent', true]]);

    requestDownload(['newsletter' => true]);

    expect(Submission::query()->where('form', 'newsletter')->sole()->data)
        ->toBe(['first_name' => 'Ana', 'last_name' => 'Lima', 'email' => 'ana@example.com', 'consent' => true]);
});

it('reports a newsletter it cannot fill, and still sends the download', function (): void {
    Exceptions::fake();
    newsletterAsks([['email', 'email', true], ['tel', 'phone', true]]);

    requestDownload(['newsletter' => true]);

    Exceptions::assertReported(NewsletterSignupFailed::class);
    Mail::assertQueued(DownloadLinkMail::class);
    expect(Submission::query()->where('form', 'download')->count())->toBe(1)
        ->and(Submission::query()->where('form', 'newsletter')->count())->toBe(0);
});

it('runs on the queue, apart from the visitor\'s request', function (): void {
    expect(new SendDownloadLink)->toBeInstanceOf(ShouldQueue::class);
});
