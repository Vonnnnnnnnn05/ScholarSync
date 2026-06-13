<?php

namespace App\Mail;

use App\Models\CertificateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateRequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CertificateRequest $certificateRequest,
        public string $mailSubject,
        public string $title,
        public string $bodyMessage,
        public ?string $actionUrl = null,
        public ?string $actionText = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate-requests.status',
        );
    }
}
