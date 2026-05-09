<?php

namespace App\Mail;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public string $senderName,
        public string $senderEmail,
        public ?string $senderPhone,
        public string $bodyMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ново прашање за оглас: '.$this->listing->title,
            replyTo: [new Address($this->senderEmail, $this->senderName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.listing-inquiry',
            with: [
                'listing' => $this->listing,
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'senderPhone' => $this->senderPhone,
                'bodyMessage' => $this->bodyMessage,
                'url' => route('listings.show', $this->listing),
            ],
        );
    }
}
