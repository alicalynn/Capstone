<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationConfirmationNotification extends Notification
{

    public string $role;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $role = 'supplier')
    {
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Default to true if not explicitly set to false
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
        $roleText = $this->role === 'supplier' ? 'Supplier' : 'Karenderia Owner';
        
        return (new MailMessage)
            ->subject('✅ Welcome to KaPlato!')
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for registering as a **{$roleText}** on KaPlato!")
            ->line("")
            ->line("📋 **What's Next?**")
            ->line("Your account has been created successfully. Our admin team is reviewing your registration.")
            ->line("")
            ->line("You will receive an email once your account is **approved**. This usually takes 1-2 business days.")
            ->line("")
            ->line("If you have any questions, feel free to contact our support team.")
            ->action('Go to KaPlato', url(config('app.url')))
            ->line('Thank you for joining KaPlato! 🚀');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_confirmation',
            'role' => $this->role,
        ];
    }
}
