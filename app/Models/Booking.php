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
        'payment_method',
        'ewallet_type',
        'reference_number',
        'nights',
        'actual_check_in',
        'actual_check_out',
        'created_at',
        'updated_at',
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

    /**
     * Retrieves the advance deposit from the booking record or matching guest reservation.
     */
    public function getAdvanceDepositAttribute(): float
    {
        $deposit = (float) ($this->deposit ?? 0);
        if ($deposit > 0) {
            return $deposit;
        }

        if ($this->guest_id) {
            $reservation = Reservation::where('guest_id', $this->guest_id)
                ->where('deposit', '>', 0)
                ->where(function ($q) {
                    $q->where('status', 'checked_in')
                        ->orWhere('status', 'reserved');
                })
                ->latest('id')
                ->first();

            if ($reservation && (float) $reservation->deposit > 0) {
                return (float) $reservation->deposit;
            }
        }

        return 0.0;
    }

    /**
     * Retrieves the human-readable stay type label (e.g. 3 Hours, 6 Hours, 12 Hours, or 24 Hours).
     */
    public function getStayTypeDisplayAttribute(): string
    {
        if ($this->stay_type === 'short_stay') {
            if ($this->short_stay_hours) {
                return $this->short_stay_hours.' Hours';
            }
            if ($this->check_in_datetime && $this->check_out_datetime) {
                $hours = round($this->check_in_datetime->diffInMinutes($this->check_out_datetime) / 60);
                if ($hours > 0) {
                    return $hours.' Hours';
                }
            }

            return 'Short Stay';
        }

        if ($this->stay_type === 'overnight') {
            return '24 Hours';
        }

        if ($this->stay_type) {
            $formatted = trim(str_ireplace(['stay', '_'], ['', ' '], (string) $this->stay_type));
            if (is_numeric($formatted)) {
                return $formatted.' Hours';
            }

            return ucwords(str_replace('_', ' ', (string) $this->stay_type));
        }

        return '24 Hours';
    }
}
