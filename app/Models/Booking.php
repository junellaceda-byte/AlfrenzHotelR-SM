<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // Protects the fillable attributes
    protected $fillable = [
        'guest_id',
        'guests',
        'short_stay_hours', 
        'with_breakfast', 
        'extra_beds', 
        'discount_type',
        'phone',
        'check_in_datetime',
        'check_out_datetime',
        'booked_rooms',
        'stay_type',
        'status',
        'deposit',
        'nights',
        'actual_check_in',
        'actual_check_out',
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
        return $this->hasMany(AvailableAmenity::class);
    }
    // Creates a one-to-many relationship with Charges
    public function charges()
    {
        return $this->hasMany(Charge::class);
    }
}
