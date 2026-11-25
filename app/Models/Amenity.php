<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'image',
        'price',
        'charged',
        'offered',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'offered' => 'boolean',
    ];

    /**
     * Get the resolved URL for the amenity image, with fallback to default cube placeholder.
     */
    public function getImageUrlAttribute(): string
    {
        if (! empty($this->image)) {
            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }

            if (file_exists(public_path($this->image))) {
                return asset($this->image);
            }

            if (file_exists(public_path('images/amenities/'.$this->image))) {
                return asset('images/amenities/'.$this->image);
            }

            return asset($this->image);
        }

        return asset('images/amenities/default-amenity.svg');
    }

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
