<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    //
    protected $fillable = [
        'booking_id',
        'invoice_number',
        'room_charges',
        'extra_charges_total',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'status',
        'due_date',
    ];
    // Booking Model
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    
    // Available Amenties Model
    public function availableAmenities()
    {
        return $this->hasMany(AvailableAmenity::class, 'invoice_id');
    }

    /** Alias used by the billing view to display added amenties on an invoice. */
    public function amenities()
    {
        return $this->hasMany(AvailableAmenity::class, 'invoice_id');
    }

    // Payment Log Model
    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }
}
