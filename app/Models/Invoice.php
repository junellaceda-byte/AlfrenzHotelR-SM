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

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function availableAmenities()
    {
        return $this->hasMany(AvailableAmenity::class, 'invoice_id');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }
}
