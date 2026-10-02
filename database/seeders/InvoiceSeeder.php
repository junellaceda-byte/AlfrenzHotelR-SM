<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Backfill unpaid invoices for every booking that does not already have one.
     * Room charges default to the overnight_rate for 1 night since we don't know
     * the original stay_type for legacy bookings.
     */
    public function run(): void
    {
        $bookingsWithoutInvoice = Booking::doesntHave('invoice')->with('invoice')->get();

        foreach ($bookingsWithoutInvoice as $booking) {
            // Calculate a best-guess room charge using overnight_rate × nights
            $rooms = Room::whereIn('id', $booking->booked_rooms ?? [])->get();
            $roomCharges = 0;

            // Use PHP native DateTime to compute nights (no Carbon)
            $checkIn = new \DateTime(date('Y-m-d H:i:s', strtotime((string) $booking->check_in_datetime)));
            $checkOut = new \DateTime(date('Y-m-d H:i:s', strtotime((string) $booking->check_out_datetime)));
            $nights = max(1, (int) $checkIn->diff($checkOut)->days);

            foreach ($rooms as $room) {
                $roomCharges += $room->overnight_rate * $nights;
            }

            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-'.strtoupper(uniqid()),
                'room_charges' => $roomCharges,
                'grand_total' => $roomCharges,
                'status' => 'unpaid',
                'due_date' => date('Y-m-d', strtotime((string) $booking->check_out_datetime)),
            ]);
        }

        $this->command->info("Created invoices for {$bookingsWithoutInvoice->count()} existing booking(s).");
    }
}
