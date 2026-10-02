<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    // Protects the fillable attributes
    protected $fillable = [
        'room_number',
        'price',
        'status',
        'short_stay_rate',
        'overnight_rate',
    ];
}
