<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomBlock extends Model
{
    protected $fillable = ['room_id', 'start_date', 'end_date', 'reason', 'is_active'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
