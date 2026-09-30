<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    //
    protected $fillable = [
        'room_number',
        'price',
        'status',
        'short_stay_rate',
        'overnight_rate',
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
