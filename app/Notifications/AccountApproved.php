<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
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
        $currentDate = Carbon::now()->toFormattedDateString();
        $salute = getSalutation();

        return (new MailMessage)
            ->subject('Account Approved')
            ->greeting("{$salute}, {$this->userName}")
            ->line("We are excited to inform you that your account with Shama Rugby has been approved on {$currentDate}.")
            ->line('You can now access all the features and services provided by Shama Rugby.')
//            ->action('Visit Shama Rugby', 'https://srf.co.ke')
            ->line('Thank you for choosing Shama Rugby for your rugby needs.')
            ->line('If you have any questions or need assistance, please feel free to contact us.');
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
