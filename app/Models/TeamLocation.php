<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_name',
        'image',
        'description',
        'location',
        'created_by',
        'status',
    ];

    // TeamLocation.php
    public function teamLocationUsers(): HasMany
    {
        return $this->hasMany(TeamLocationUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_location_users')->withPivot('role');
    }

    // Define the relationship with Team
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

}
