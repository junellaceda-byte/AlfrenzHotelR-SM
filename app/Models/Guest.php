<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'fname',
        'lname',
        'phone', // optional — guest may decline to share
    ];

    /** A guest can have many direct bookings (check-ins). */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /** A guest can also have advance reservations. */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
