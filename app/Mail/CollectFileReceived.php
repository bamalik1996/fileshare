<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollectFileReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Share $share,
        public string $fileName,
        public int $fileSize,
    ) {
    }

    public function envelope(): Envelope
    {
        $label = $this->share->collect_title ?: 'file request';

        return new Envelope(
            subject: 'New file received on your AirToShare ' . $label,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.collect-file-received',
        );
    }
}
