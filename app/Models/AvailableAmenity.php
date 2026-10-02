<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableAmenity extends Model
{
    protected $fillable = [
        'invoice_id', 'booking_id', 'item_name', 'price', 
        'is_available', 'quantity', 'unit_price', 'total_amount'
    ];
}
