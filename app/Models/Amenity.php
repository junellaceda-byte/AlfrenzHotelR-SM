<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'price',
        'charged',
        'offered',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'offered' => 'boolean',
    ];

    /**
     * Compatibility accessor for item_name (used in billing & receptionist templates)
     */
    public function getItemNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    /**
     * Compatibility accessor for is_available (used in billing & receptionist templates)
     */
    public function getIsAvailableAttribute(): bool
    {
        return (bool) ($this->attributes['offered'] ?? false);
    }
}
