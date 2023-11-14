<?php

namespace App\Helpers;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;

class ActivityHelper
{
    public static function logActivity($userId, $title): void
    {
        // Create a new Activity instance
        $activity = new Activity();

        // Set the values for the fields
        $activity->user_id = $userId;
        $activity->title = $title;

        // Automatically set the timestamp
        $activity->timestamps = false; // Disable Laravel's default timestamp handling
        $activity->created_at = Carbon::now();
        $activity->updated_at = Carbon::now();

        // Save the activity
        $activity->save();
    }

    public static function getUserName($userId): string
    {
        $user = User::where('id', $userId)->first();
        return $user->first_name." ".$user->last_name;
    }
}
