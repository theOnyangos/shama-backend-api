<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspended extends Notification
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
            ->subject('Account Suspended')
            ->greeting("{$salute}, {$this->userName}")
            ->line('Your account with Shama Rugby has been suspended by the admin. Date of suspension '.$currentDate)
            ->line('If you believe this is a mistake or have any questions, please contact our support team.')
            ->line('Thank you for using Shama Rugby.');
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
