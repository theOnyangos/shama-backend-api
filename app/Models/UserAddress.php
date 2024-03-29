<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "address",
        "city",
        "county_id",
        "region_id",
        "street_id",
        "coach_id",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'county_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'street_id');
    }
}
