<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
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
    public function bookings()
    {
        return $this->belongsTo(Booking::class);
    }

    public function amenities()
    {
        return $this->hasMany(Amenities::class);
    }
    
    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }
}
