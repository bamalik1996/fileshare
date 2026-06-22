<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareExpiryReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Share $share,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AirToShareA share is expiring soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.share-expiry-reminder',
        );
    }
}
