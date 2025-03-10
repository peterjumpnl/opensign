<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class DocumentCompleted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The document instance.
     *
     * @var \App\Models\Document
     */
    public $document;

    /**
     * The attachments for the email.
     *
     * @var array
     */
    public $attachments;

    /**
     * Create a new message instance.
     */
    public function __construct(Document $document, array $attachments = [])
    {
        $this->document = $document;
        $this->attachments = $attachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Completed: ' . $this->document->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.document-completed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $mailAttachments = [];

        foreach ($this->attachments as $attachment) {
            if (isset($attachment['path']) && isset($attachment['name'])) {
                $mailAttachments[] = Attachment::fromPath($attachment['path'])
                    ->as($attachment['name']);
            }
        }

        return $mailAttachments;
    }
}
