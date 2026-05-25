<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SupplyOrder;

class OwnerConfirmedDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private SupplyOrder $order;

    public function __construct(SupplyOrder $order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        if (!$notifiable->email_notifications_enabled) {
            return [];
        }
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $karenderia = $this->order->karenderia;
        $owner = $this->order->karenderia->owner;

        return (new MailMessage)
            ->subject("Order #{$this->order->id} Delivery Confirmed - Ready to Mark Delivered")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$owner->name} from {$karenderia->business_name} has confirmed receipt of order #{$this->order->id}.")
            ->line('You can now mark this order as delivered on your supplier dashboard.')
            ->action('Go to Dashboard', config('app.frontend_url') . '/inventory-management')
            ->line('Order Details:')
            ->line("• Order ID: {$this->order->id}")
            ->line("• Karenderia: {$karenderia->business_name}")
            ->line("• Owner: {$owner->name}")
            ->line("• Total Amount: ₱" . number_format($this->order->total_amount, 2))
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'owner_confirmed_delivery',
            'order_id' => $this->order->id,
            'karenderia_name' => $this->order->karenderia->business_name,
        ];
    }
}
