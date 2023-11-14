<?php

namespace App\Helpers;

use App\Notifications\NotifyAdmin;

class NotificationHelper
{
    public static function notifyAdmin($message, $adminData): void
    {
        \Notification::route('mail', $adminData['email'])->notify(new NotifyAdmin($message, $adminData['user_name']));
    }
}
