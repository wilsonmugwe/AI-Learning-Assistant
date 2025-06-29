<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'filename',
        'content',
        'summary',
        'bullet_summary', // Now plain text, not array
    ];

    // Removed $casts because bullet_summary is no longer treated as array
}
