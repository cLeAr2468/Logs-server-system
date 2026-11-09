<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $status;
    public $studentName;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct($transaction, $status, $studentName, $rejectionReason = null)
    {
        $this->transaction = $transaction;
        $this->status = $status;
        $this->studentName = $studentName;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Appointment ' . ucfirst($this->status);
        
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
            view: 'emails.transaction-status',
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
