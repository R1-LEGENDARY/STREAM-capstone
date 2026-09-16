<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    protected $fillable = [
        'temperature', 'ph', 'dissolved_oxygen', 'recorded_at', 'seen'
    ];
    protected $casts = [
        'recorded_at' => 'datetime',
        'seen' => 'boolean',
    ];
}
