<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
