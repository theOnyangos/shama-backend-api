<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreated extends Notification
{
    use Queueable;
    public $userName;

    /**
     * Create a new notification instance.
     */
    public function __construct($userName)
    {
        $this->userName = $userName;
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
        $salute = getSalutation();

        return (new MailMessage)
            ->subject('Account Creation')
            ->greeting("{$salute}, {$this->userName}")
            ->line('Welcome to Shama Rugby!')
            ->line('Your account has been successfully created. You will be notified once your account is approved, this might take up to 24hours.')
            ->line('If you have any queries feel free to contact us at support@srf.co.ke.')
            ->line('Thank you for joining our community.');
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
