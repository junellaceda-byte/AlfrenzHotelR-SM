<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // Protects the fillable attributes
    protected $fillable = [
        'guest_id',
        'phone',
        'check_in_datetime',
        'check_out_datetime',
        'booked_rooms',
    ];

    protected $casts = [
        'booked_rooms' => 'array',
        'check_in_datetime' => 'datetime:Y-m-d g:i A',
        'check_out_datetime' => 'datetime:Y-m-d g:i A',
    ];

    // Creates a one-to-one relationship with Guest
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    // Creates a one-to-one relationship with Invoice
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Creates a one-to-many relationship with Amenities
    public function amenities()
    {
        return $this->hasMany(Amenities::class);
    }
}
