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

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-'.strtoupper(uniqid());
            }
        });
    }

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

    /**
     * Retrieves the advance deposit carried over to this invoice's booking.
     */
    public function getAdvanceDepositAttribute(): float
    {
        return (float) ($this->booking?->advance_deposit ?? $this->booking?->deposit ?? 0);
    }

    /**
     * Calculates the remaining balance due after crediting the advance deposit.
     */
    public function getRemainingBalanceAttribute(): float
    {
        $deposit = $this->advance_deposit;
        $grandTotal = (float) $this->grand_total;

        return max(0.0, round($grandTotal - $deposit, 2));
    }
}
