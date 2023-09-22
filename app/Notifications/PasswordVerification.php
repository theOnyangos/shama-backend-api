<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordVerification extends Notification
{
    use Queueable;
    public $message;
    public $userName;

    /**
     * Create a new notification instance.
     */
    public function __construct($userName, $message)
    {
        $this->userName = $userName;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Hello, ' . $this->userName . '!')
                    ->line($this->message['first'])
                    ->line($this->message['second'])
//                    ->action('Verify Email', route('verification.verify'))
                    ->line('If you did not request this code, no action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
