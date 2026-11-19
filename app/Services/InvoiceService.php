<?php

namespace App\Services;

use App\Models\Booking;

class InvoiceService
{
    // The InvoiceService is responsible for recalculating the
    // invoice totals based on the booking's charge lines, which include room charges,
    // extra charges, and discounts. It uses the PricingService to determine the discount rate based on the booking's discount type.
    public function __construct(private PricingService $pricing) {}

    /** Rebuilds the invoice totals from the booking's charge lines (rooms + amenities - discount) */
    public function recalculate(Booking $booking): void
    {
        $invoice = $booking->invoice;

        if (! $invoice) {
            return;
        }

        $charges = $booking->charges()->get();
        $rooms = (float) $charges->where('type', 'room')->sum('amount');
        $extras = (float) $charges->where('type', '!=', 'room')->sum('amount');
        $subtotal = $rooms + $extras;
        $discount = round($subtotal * $this->pricing->discountRate($booking->discount_type), 2);

        $invoice->update([
            'room_charges' => $rooms,
            'extra_charges_total' => $extras,
            'discount_amount' => $discount,
            'grand_total' => round($subtotal - $discount, 2),
        ]);
    }
}
