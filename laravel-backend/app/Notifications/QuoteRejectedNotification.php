<?php

namespace App\Notifications;

use App\Models\SupplierQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public SupplierQuote $quote;

    /**
     * Create a new notification instance.
     */
    public function __construct(SupplierQuote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Only send email if user has notifications enabled
        if (!$notifiable->email_notifications_enabled) {
            return [];
        }
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $karenderia = $this->quote->ingredientRequest->karenderia;
        $request = $this->quote->ingredientRequest;

        return (new MailMessage)
            ->subject('❌ Your Quote Was Not Selected')
            ->greeting("Hello {$notifiable->name},")
            ->line("Unfortunately, your quote for {$request->ingredient_name} was not selected by **{$karenderia->business_name}**.")
            ->line("**Your Quote Details:**")
            ->line("- **Quantity:** {$this->quote->available_quantity} {$this->quote->unit}")
            ->line("- **Total Price:** ₱" . number_format($this->quote->total_price, 2))
            ->line("- **Delivery Date:** {$this->quote->delivery_date}")
            ->line("")
            ->line("Keep submitting quotes! You might have better luck with other requests.")
            ->action('View More Requests', url(config('app.url') . '/supplier/requests'))
            ->line('Thank you for using KaPlato! 🚀');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'quote_id' => $this->quote->id,
            'status' => 'rejected',
        ];
    }
}
