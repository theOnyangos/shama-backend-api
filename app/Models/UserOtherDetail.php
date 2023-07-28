<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtherDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "emergency_contact_name",
        "emergency_contact_phone",
        "emergency_contact_email",
        "emergency_notes",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
