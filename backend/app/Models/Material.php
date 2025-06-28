<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'filename',
        'content',
        'summary',
        'bullet_summary',  // bullet_summary 
    ];

    protected $casts = [
        'bullet_summary' => 'array',  // Cast bullet_summary as array
    ];
}
