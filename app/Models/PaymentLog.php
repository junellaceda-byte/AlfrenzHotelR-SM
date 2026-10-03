<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Invoice Model relationship
use App\Models\Invoice;

class PaymentLog extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'amount_paid',
        'payment_method',
        'status',
        'created_at',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
