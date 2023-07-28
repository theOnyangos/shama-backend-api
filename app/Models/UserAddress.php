<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
