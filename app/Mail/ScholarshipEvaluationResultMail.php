<?php

namespace App\Mail;

use App\Models\ScholarshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScholarshipEvaluationResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ScholarshipApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scholarship Renewal '.$this->application->status->label(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scholarship-applications.result',
        );
    }
}
