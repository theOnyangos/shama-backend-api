<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "school_level",
        "school_address",
        "school_city",
        "school_phone",
        "school_email",
        "school_grade",
        "school_counselor_name",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
