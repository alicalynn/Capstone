<?php

namespace App\Notifications;

use App\Models\SupplierQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
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
            ->subject('✅ Your Quote Has Been Accepted!')
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your quote has been accepted by **{$karenderia->business_name}**.")
            ->line("**Ingredient:** {$request->ingredient_name}")
            ->line("**Quantity:** {$this->quote->available_quantity} {$this->quote->unit}")
            ->line("**Total Price:** ₱" . number_format($this->quote->total_price, 2))
            ->line("**Delivery Date:** {$this->quote->delivery_date}")
            ->line("**Delivery Method:** " . ucfirst($this->quote->delivery_method))
            ->action('View Details in KaPlato', url(config('app.url') . '/supplier-request-detail/' . $request->id))
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
            'status' => 'accepted',
        ];
    }
}
