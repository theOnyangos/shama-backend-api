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
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }
}
