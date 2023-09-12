<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamLocationUser extends Model
{
    use HasFactory;

    protected $table = 'team_location_user';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
    ];

    // Define the relationship with User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship with TeamLocation
    public function teamLocation(): BelongsTo
    {
        return $this->belongsTo(TeamLocation::class);
    }

    // Define a relationship with Team
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
