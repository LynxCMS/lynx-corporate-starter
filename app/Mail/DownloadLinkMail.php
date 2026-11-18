<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The link to a requested download, for visitors who come back to it later. */
final class DownloadLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $url, public readonly string $title, public readonly string $languageName, public readonly int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('site.download_mail.subject', ['title' => $this->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.download-link');
    }
}
