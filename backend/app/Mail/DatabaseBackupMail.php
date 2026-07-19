<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $backupPath,
        private readonly string $backupFilename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'بک‌آپ پایگاه داده پوشه — '.now()->format('Y-m-d H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>بک‌آپ خودکار پایگاه داده سامانه پوشه در پیوست ارسال شده است.</p>'
                .'<p>تاریخ: '.now()->format('Y-m-d H:i:s').'</p>',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->backupPath)
                ->as($this->backupFilename)
                ->withMime('application/gzip'),
        ];
    }
}
