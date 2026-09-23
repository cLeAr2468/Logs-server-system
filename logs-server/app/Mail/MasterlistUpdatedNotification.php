<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MasterlistUpdatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $studentData;
    public $changes;
    public $updatedBy;

    /**
     * Create a new message instance.
     */
    public function __construct($studentData, $changes, $updatedBy)
    {
        $this->studentData = $studentData;
        $this->changes = $changes;
        $this->updatedBy = $updatedBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Student Information Has Been Updated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.masterlist-updated',
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
