<?php

namespace App\Notifications;

use App\Models\SupplierQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteNotification extends Notification implements ShouldQueue
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
        $supplier = $this->quote->supplier;
        $request = $this->quote->ingredientRequest;

        return (new MailMessage)
            ->subject('📬 New Quote Received for Review')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have a new quote from **{$supplier->name}** for your ingredient request!")
            ->line("")
            ->line("**Request Details:**")
            ->line("- **Ingredient:** {$request->ingredient_name}")
            ->line("- **Quantity Needed:** {$request->quantity_needed} {$request->unit}")
            ->line("")
            ->line("**Supplier's Quote:**")
            ->line("- **Price Per Unit:** ₱" . number_format($this->quote->price_per_unit, 2))
            ->line("- **Available Quantity:** {$this->quote->available_quantity} {$this->quote->unit}")
            ->line("- **Total Price:** ₱" . number_format($this->quote->total_price, 2))
            ->line("- **Delivery Date:** {$this->quote->delivery_date}")
            ->line("- **Delivery Method:** " . ucfirst($this->quote->delivery_method))
            ->line("")
            ->line("Review and compare this quote with others before making a decision.")
            ->action('Review Quote in KaPlato', url(config('app.url') . '/owner/request/' . $request->id))
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
            'request_id' => $this->quote->ingredient_request_id,
            'supplier_name' => $this->quote->supplier->name,
        ];
    }
}
