<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    protected $fillable = [
        'recorded_at',
        'dissolved_oxygen',
        'ph',
        'temperature',
    ];
}
