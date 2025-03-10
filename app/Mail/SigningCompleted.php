<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Signer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SigningCompleted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The signer instance.
     *
     * @var \App\Models\Signer
     */
    public $signer;

    /**
     * The document instance.
     *
     * @var \App\Models\Document
     */
    public $document;

    /**
     * Create a new message instance.
     */
    public function __construct(Signer $signer, Document $document)
    {
        $this->signer = $signer;
        $this->document = $document;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->signer->name . ' has signed your document: ' . $this->document->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.signing-completed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
