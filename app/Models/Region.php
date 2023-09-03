<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $table = 'shama_regions';

    protected $fillable = [
        'county_code',
        'county_name'
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'county_id');
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class, 'region_id');
    }

}
