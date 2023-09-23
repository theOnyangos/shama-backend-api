<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionConfirmation extends Notification
{
    use Queueable;
    public $userName;
    public $userId;

    /**
     * Create a new notification instance.
     */
    public function __construct($userName, $userId)
    {
        $this->userName = $userName;
        $this->userId = $userId;
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
            ->subject('Account Deletion Confirmation')
            ->greeting("{$salute}, {$this->userName}")
            ->line('We have received a request to delete your account with Shama Rugby.')
            ->line('If you did not initiate this request, please contact our support team immediately.')
            ->action('Confirm Account Deletion', url("/api/v1/save-closure-reason/{$this->userId}"))
            ->line('Thank you for using Shama Rugby Application.');
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
