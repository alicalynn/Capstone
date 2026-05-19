<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Message $message;
    public User $fromUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message, User $fromUser)
    {
        $this->message = $message;
        $this->fromUser = $fromUser;
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
        $request = $this->message->ingredientRequest;
        $karenderia = $request->karenderia;

        $messagePreview = substr($this->message->message, 0, 100) . (strlen($this->message->message) > 100 ? '...' : '');

        return (new MailMessage)
            ->subject("💬 New Message from {$this->fromUser->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->fromUser->name} sent you a message about **{$request->title}**:")
            ->line("_{$messagePreview}_")
            ->action('View Conversation', url("/requests/{$request->id}"))
            ->line('Thank you for using KaPlato!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'from_user_id' => $this->fromUser->id,
            'from_user_name' => $this->fromUser->name,
            'ingredient_request_id' => $this->message->ingredient_request_id,
            'message_preview' => substr($this->message->message, 0, 100),
            'type' => 'message_sent',
        ];
    }
}
