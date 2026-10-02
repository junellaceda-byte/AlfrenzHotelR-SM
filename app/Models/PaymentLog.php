<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment_logs extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'amount_paid',
        'payment_method',
        'status',
        'created_at'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
