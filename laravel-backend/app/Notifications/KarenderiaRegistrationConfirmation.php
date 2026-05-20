<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KarenderiaRegistrationConfirmation extends Notification
{
    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        if ($notifiable->email_notifications_enabled === false) {
            return [];
        }
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Welcome to KaPlato - Karenderia Owner!')
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for registering as a **Karenderia Owner** on KaPlato!")
            ->line("")
            ->line("📋 **What's Next?**")
            ->line("Your account has been created successfully. Our admin team is reviewing your registration and business documents.")
            ->line("")
            ->line("✋ **Please be patient** - You will receive an email once your account is **approved**. This usually takes 1-2 business days.")
            ->line("")
            ->line("During the review process, admin will verify:")
            ->line("- Your business permit validity")
            ->line("- Business information accuracy")
            ->line("- Compliance with KaPlato guidelines")
            ->line("")
            ->line("Once approved, you can:")
            ->line("- ✅ Log in to your dashboard")
            ->line("- ✅ Add menu items and meals")
            ->line("- ✅ Set operating hours and delivery options")
            ->line("- ✅ Manage customer orders")
            ->line("- ✅ View analytics and reports")
            ->line("")
            ->line("If you have any questions, feel free to contact our support team.")
            ->action('Go to KaPlato', url(config('app.url')))
            ->line('Thank you for joining KaPlato! 🚀');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'karenderia_registration_confirmation',
            'role' => 'karenderia_owner',
        ];
    }
}
