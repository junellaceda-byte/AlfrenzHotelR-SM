<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    // Protects the fillable attributes
    protected $fillable = [
        'room_number',
        'price',
        'status',
        'short_stay_rate',
        'overnight_rate',
    ];

    /**
     * Format a room number so single-digit numbers (0-9) display with a leading zero (01-09),
     * while 10 and above (or non-numeric suffixes) remain unchanged.
     */
    public static function formatRoomNumber(string|int|null $room): string
    {
        $value = trim((string) $room);
        if ($value === '') {
            return '';
        }

        if (ctype_digit($value) && strlen($value) === 1) {
            return str_pad($value, 2, '0', STR_PAD_LEFT);
        }

        return $value;
    }
}
