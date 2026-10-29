<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\Room;

class BookingService
{   
    // The BookingService class is 
    //responsible for creating bookings, calculating prices, and generating invoices.
    // It uses the PricingService to calculate the total cost based on the booking details provided.
    public function __construct(private PricingService $pricing)
    {
    }

    /** Creates the booking, its charge lines and its unpaid invoice. Returns [booking, price]. */
    public function create(array $d): array
    {
        $isShort = $d['stay_type'] === 'short_stay';
        $withBreakfast = (bool) ($d['with_breakfast'] ?? false);
        $extraBeds = $isShort ? 0 : (int) ($d['extra_beds'] ?? 0);
        $status = $d['status'] ?? 'checked_in';

        $checkIn = $d['check_in'];
        $checkOut = $checkIn->copy()->addHours($isShort ? (int) $d['short_hours'] : 24);

        $price = $this->pricing->calculate([
            'stay_type' => $d['stay_type'],
            'short_hours' => $d['short_hours'] ?? null,
            'with_breakfast' => $withBreakfast,
            'extra_beds' => $extraBeds,
            'discount_type' => $d['discount_type'] ?? null,
        ], count($d['room_ids']));

        $booking = Booking::create([
            'guest_id' => $d['guest_id'],
            'phone' => $d['phone'],
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'booked_rooms' => $d['room_ids'],
            'guests' => $d['guests'] ?? 1,
            'stay_type' => $d['stay_type'],
            'short_stay_hours' => $isShort ? (int) $d['short_hours'] : null,
            'with_breakfast' => $withBreakfast,
            'extra_beds' => $extraBeds,
            'discount_type' => $d['discount_type'] ?: null,
            'nights' => $isShort ? null : 1,
            'deposit' => $d['deposit'] ?? 0,
            'status' => $status,
        ]);

        if ($status === 'checked_in') {
            Room::whereIn('id', $d['room_ids'])->update(['status' => 'occupied']);
        }

        // One charge line per room, plus one line for extra beds
        $stayLabel = $isShort
            ? $d['short_hours'] . '-hour short stay'
            : '24-hour stay' . ($withBreakfast ? ' with breakfast' : '');

        foreach (Room::whereIn('id', $d['room_ids'])->get() as $room) {
            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'room',
                'description' => 'Room ' . str_pad($room->id, 2, '0', STR_PAD_LEFT) . ' - ' . $stayLabel,
                'quantity' => 1,
                'unit_price' => $price['room_price'],
                'amount' => $price['room_price'],
            ]);
        }

        if ($extraBeds > 0) {
            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'extra_bed',
                'description' => 'Extra bed',
                'quantity' => $extraBeds,
                'unit_price' => $price['bed_price'],
                'amount' => $price['beds'],
            ]);
        }

        // Unpaid invoice: payment is taken at check-out
        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'room_charges' => $price['rooms'],
            'extra_charges_total' => $price['beds'],
            'discount_amount' => $price['discount'],
            'tax_amount' => 0,
            'grand_total' => $price['total'],
            'status' => 'unpaid',
            'due_date' => $checkOut->toDateString(),
        ]);

        return [$booking, $price];
    }
}