<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'player_documents';

    protected $fillable = [
        'user_id',
        'document_path',
        'created_at',
    ];
}
