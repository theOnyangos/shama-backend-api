<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    use HasFactory;

    protected $table = 'shama_counties';

    protected $fillable = [
        'county_code',
        'county_name',
        'in_county'
    ];

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class, 'county_id');
    }

}
