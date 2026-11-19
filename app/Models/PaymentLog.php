<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Invoice Model relationship

class PaymentLog extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'amount_paid',
        'payment_method',
        'status',
        'paid_at',
        'created_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
