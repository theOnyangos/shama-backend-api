<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Street extends Model
{
    use HasFactory;

    protected $table = 'shama_streets';

    protected $fillable = [
        'county_id',
        'region_id',
        'street_name',
        'amount',
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'county_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function userAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'street_id');
    }
}
