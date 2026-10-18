<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    /**
     * Fields that can be saved in bulk.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guest_name',
        'guest_id',
        'phone_number',
        'reservation_start_date',
        'reservation_end_date',
        'number_of_rooms',
        'selected_rooms',
        'adults_count',
        'children_count',
        'status',
        'stay_type',
        'deposit'
    ];

    /**
     * Tell Laravel how to convert certain columns automatically.
     * selected_rooms is stored in the database as JSON text,
     * but Laravel will turn it into a normal PHP array for you — no JSON code needed.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selected_rooms' => 'array',
        'reservation_start_date' => 'datetime:Y-m-d g:i A',
        'reservation_end_date' => 'datetime:Y-m-d g:i A',
    ];

    /** Each reservation belongs to one guest. */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
