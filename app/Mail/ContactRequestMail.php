<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $contactName,
        public readonly string $contactEmail,
        public readonly string $purposeLabel,
        public readonly ?string $company,
        public readonly ?string $phone,
        public readonly string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe contactaanvraag via Salesenmarketingvacatures.nl',
            replyTo: [new Address($this->contactEmail, $this->contactName)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-request');
    }
}
