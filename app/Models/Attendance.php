<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        "coach_id",
        "team_id",
        "attendance_type",
        "attendees",
        "description",
    ];

    // Define users relationship with attendance
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

}
