<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistTableDataRowsBeigeGreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_reservation_table_data_rows_have_beige_green_background(): void
    {
        $room = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'available',
        ]);

        Reservation::create([
            'guest_name' => 'Alice Smith',
            'phone_number' => '09123456780',
            'reservation_start_date' => now()->addDay(),
            'reservation_end_date' => now()->addDays(2),
            'stay_type' => 'overnight',
            'with_breakfast' => false,
            'selected_rooms' => [(string) $room->id],
            'number_of_rooms' => 1,
            'status' => 'reserved',
        ]);

        $response = $this->get(route('reservation.index'));
        $response->assertOk();

        // Data row cells have Beige Green #F2F6D0
        $response->assertSee('background-color: #F2F6D0', false);
        $response->assertSee('reservation-table', false);

        // Header retains Vintage Brown
        $response->assertSee('#6A4435', false);
    }

    public function test_booking_history_table_data_rows_have_beige_green_background(): void
    {
        $room = Room::create([
            'room_number' => '102',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'occupied',
        ]);

        $guest = Guest::create([
            'fname' => 'Bob',
            'lname' => 'Jones',
            'contact_no' => '09123456781',
        ]);

        Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [(string) $room->id],
            'check_in_datetime' => now()->subDay(),
            'check_out_datetime' => now()->addHours(5),
            'stay_type' => 'overnight',
            'status' => 'checked_in',
        ]);

        $response = $this->get(route('booking.index'));
        $response->assertOk();

        // Data row cells have Beige Green #F2F6D0
        $response->assertSee('background-color: #F2F6D0', false);
        $response->assertSee('booking-history-table', false);

        // Header retains Vintage Brown
        $response->assertSee('#6A4435', false);
    }

    public function test_billing_pending_and_confirmed_table_data_rows_have_beige_green_background(): void
    {
        $room = Room::create([
            'room_number' => '103',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'occupied',
        ]);

        $guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'contact_no' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'check_in_datetime' => Carbon::now()->subHours(2),
            'check_out_datetime' => Carbon::now()->addHours(2),
            'stay_type' => 'short_stay',
            'booked_rooms' => [$room->id],
            'status' => 'checked_in',
        ]);

        $pendingInvoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 500.00,
            'amenities_charges' => 0.00,
            'other_charges' => 0.00,
            'grand_total' => 500.00,
            'status' => 'unpaid',
            'payment_status' => 'unpaid',
        ]);

        $confirmedInvoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 500.00,
            'amenities_charges' => 0.00,
            'other_charges' => 0.00,
            'grand_total' => 500.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        PaymentLog::create([
            'invoice_id' => $confirmedInvoice->id,
            'amount_paid' => 500.00,
            'payment_method' => 'cash',
        ]);

        $response = $this->get(route('content.billing'));
        $response->assertOk();

        // Data row cells have Beige Green #F2F6D0
        $response->assertSee('background-color: #F2F6D0', false);
        $response->assertSee('--bs-table-bg: #F2F6D0', false);
        $response->assertSee('billing-pending-table', false);
        $response->assertSee('billing-confirmed-table', false);

        // Header retains Vintage Brown
        $response->assertSee('background-color: #6A4435', false);
    }
}
