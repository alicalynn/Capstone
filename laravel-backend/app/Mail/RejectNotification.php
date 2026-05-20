<?php

namespace App\Mail;

use App\Models\Karenderia;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RejectNotification extends Mailable
{
    use SerializesModels;

    public Karenderia $karenderia;
    public string $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(Karenderia $karenderia, string $rejectionReason)
    {
        $this->karenderia = $karenderia;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Karenderia Application was Rejected - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $ownerEmail = $this->karenderia->owner?->email ?? '';
        $reapplyUrl = config('app.frontend_url', config('app.url')) . '/owner-reapply?email=' . urlencode($ownerEmail);

        return new Content(
            view: 'emails.owner-rejection-notification',
            with: [
                'karenderia' => $this->karenderia,
                'owner' => $this->karenderia->owner,
                'rejectionReason' => $this->rejectionReason,
                'reapplyUrl' => $reapplyUrl,
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
