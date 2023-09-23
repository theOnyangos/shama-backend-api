<?php
function getSalutation(): string
{
    date_default_timezone_set('Africa/Nairobi');
    $currentTime = date("H:i"); // Get the current time in "HH:mm" format

    if (strtotime($currentTime) >= strtotime("05:00") && strtotime($currentTime) < strtotime("12:00")) {
        return "Good morning";
    } elseif (strtotime($currentTime) >= strtotime("12:00") && strtotime($currentTime) < strtotime("17:59")) {
        return "Good afternoon";
    } else {
        return "Good evening";
    }
}


