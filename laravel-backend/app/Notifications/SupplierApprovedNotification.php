<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $roleText = $this->role === 'supplier' ? 'Supplier' : 'Karenderia Owner';
        $actionUrl = $this->role === 'supplier' 
            ? url(config('app.url') . '/supplier/requests') 
            : url(config('app.url') . '/owner/dashboard');
        
        return (new MailMessage)
            ->subject('🎉 Your Account Has Been Approved!')
            ->greeting("Hello {$notifiable->name},")
            ->line("Congratulations! Your {$roleText} account has been **approved** by our admin team.")
            ->line("")
            ->line("✅ **You can now start using KaPlato!**")
            ->line("")
            ->line("What you can do now:")
            ->line("- " . ($this->role === 'supplier' 
                ? "Browse available ingredient requests from karenderia owners" 
                : "Create ingredient requests and review supplier quotes"))
            ->line("- Update your profile and preferences")
            ->line("- Start collaborating with other users on the platform")
            ->line("")
            ->action('Login to KaPlato', $actionUrl)
            ->line('Welcome aboard! 🚀');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_approved',
            'role' => $this->role,
        ];
    }
}
