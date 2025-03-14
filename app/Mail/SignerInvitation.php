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

class SignerInvitation extends Mailable
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
     * The signing URL.
     *
     * @var string
     */
    public $signingUrl;
    
    /**
     * Whether this is a reminder email.
     *
     * @var bool
     */
    public $isReminder;

    /**
     * Create a new message instance.
     */
    public function __construct(Signer $signer, Document $document, bool $isReminder = false)
    {
        $this->signer = $signer;
        $this->document = $document;
        $this->isReminder = $isReminder;
        $this->signingUrl = route('sign.show', [
            'signer' => $signer->id,
            'document' => $document->id,
            'token' => $signer->access_token
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isReminder 
            ? 'REMINDER: Document Signing Request: ' . $this->document->title
            : 'Document Signing Request: ' . $this->document->title;
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.signer-invitation',
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
