<?php

namespace App\Services;

class PricingService
{
    /* This service calculates the price of a booking based on the stay type,
    number of rooms, extra beds, and any applicable discounts. 
    It retrieves pricing information from the configuration file.*/
    
    /** Price of one room for the chosen stay */
    public function roomPrice(string $stayType, $shortHours, bool $withBreakfast): float
    {
        if ($stayType === 'short_stay') {
            return (float) config('hotel.prices.short_stay.' . (int) $shortHours, 0);
        }

        return (float) config('hotel.prices.overnight.' . ($withBreakfast ? 'with_breakfast' : 'no_breakfast'), 0);
    }

    /** Price of one extra bed (24-hour stays only) */
    public function bedPrice(bool $withBreakfast): float
    {
        return (float) config('hotel.prices.extra_bed.' . ($withBreakfast ? 'with_breakfast' : 'no_breakfast'), 0);
    }

    /** 0.20 for senior or pwd, otherwise 0 */
    public function discountRate(?string $discountType): float
    {
        if (!$discountType) {
            return 0;
        }

        return (float) config('hotel.discounts.' . $discountType, 0);
    }

    /** Subtotal, discount and total for a booking */
    public function calculate(array $data, int $roomCount): array
    {
        $isShort = $data['stay_type'] === 'short_stay';
        $withBreakfast = (bool) ($data['with_breakfast'] ?? false);

        $roomPrice = $this->roomPrice($data['stay_type'], $data['short_hours'] ?? null, $withBreakfast);
        $bedPrice = $isShort ? 0 : $this->bedPrice($withBreakfast);

        $rooms = $roomPrice * $roomCount;
        $beds = $bedPrice * (int) ($data['extra_beds'] ?? 0);
        $subtotal = $rooms + $beds;
        $discount = round($subtotal * $this->discountRate($data['discount_type'] ?? null), 2);

        return [
            'room_price' => $roomPrice,
            'bed_price'  => $bedPrice,
            'rooms'      => $rooms,
            'beds'       => $beds,
            'subtotal'   => $subtotal,
            'discount'   => $discount,
            'total'      => round($subtotal - $discount, 2),
        ];
    }
}