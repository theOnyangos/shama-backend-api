<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_name',
        'team_image',
        'description',
        'coach_id',
        'admin_id',
    ];

    // Define the relationship with the Coach (User) model
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Define a relationship with TeamLocation
    public function teamLocations(): HasMany
    {
        return $this->hasMany(TeamLocation::class);
    }

    // Define a relationship with TeamLocationUser
    public function members(): HasMany
    {
        return $this->hasMany(TeamLocationUser::class, 'team_id');
    }

}
