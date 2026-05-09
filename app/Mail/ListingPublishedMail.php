<?php

namespace App\Mail;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Listing $listing) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Вашиот оглас е објавен — '.$this->listing->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.listing-published',
            with: [
                'listing' => $this->listing,
                'url' => route('listings.show', $this->listing),
            ],
        );
    }
}
