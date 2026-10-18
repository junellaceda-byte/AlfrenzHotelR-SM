<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    //
    protected $fillable = [
        'booking_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'is_paid'
    ];
}
