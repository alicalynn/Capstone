<?php

namespace App\Mail;

use App\Models\Karenderia;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KarenderiaApprovedNotification extends Mailable
{
    use SerializesModels;

    public Karenderia $karenderia;

    /**
     * Create a new message instance.
     */
    public function __construct(Karenderia $karenderia)
    {
        $this->karenderia = $karenderia;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Karenderia Application was Approved! 🎉 - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.owner-approval-notification',
            with: [
                'karenderia' => $this->karenderia,
                'owner' => $this->karenderia->owner ?? null,
            ],
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
