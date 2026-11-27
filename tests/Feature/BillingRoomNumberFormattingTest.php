<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingRoomNumberFormattingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_room_format_helper_pads_single_digits_and_preserves_ten_and_above(): void
    {
        $this->assertSame('01', Room::formatRoomNumber(1));
        $this->assertSame('05', Room::formatRoomNumber('5'));
        $this->assertSame('09', Room::formatRoomNumber('09'));
        $this->assertSame('10', Room::formatRoomNumber(10));
        $this->assertSame('101', Room::formatRoomNumber('101'));
        $this->assertSame('40A', Room::formatRoomNumber('40A'));
        $this->assertSame('', Room::formatRoomNumber(null));
    }

    public function test_billing_pending_and_confirmed_tables_format_single_digit_rooms_with_leading_zeros(): void
    {
        $guest = Guest::create([
            'fname' => 'Elena',
            'lname' => 'Reyes',
            'contact_no' => '09171234567',
            'email' => 'elena@example.com',
        ]);

        $pendingBooking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => ['3', '10'],
            'stay_type' => 'overnight',
            'nights' => 1,
            'check_in_datetime' => now()->subDay(),
            'check_out_datetime' => now()->addHour(),
            'status' => 'checked_in',
            'total_amount' => 4400.00,
        ]);

        Invoice::create([
            'booking_id' => $pendingBooking->id,
            'room_charges' => 4400.00,
            'extra_charges_total' => 0,
            'grand_total' => 4400.00,
            'status' => 'unpaid',
        ]);

        $confirmedBooking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [7, 12],
            'stay_type' => 'overnight',
            'nights' => 1,
            'check_in_datetime' => now()->subDays(2),
            'check_out_datetime' => now()->subDay(),
            'status' => 'checked_out',
            'total_amount' => 4400.00,
        ]);

        Invoice::create([
            'booking_id' => $confirmedBooking->id,
            'room_charges' => 4400.00,
            'extra_charges_total' => 0,
            'grand_total' => 4400.00,
            'status' => 'paid',
        ]);

        $response = $this->get(route('content.billing'));

        $response->assertOk();
        $response->assertSee('03, 10', false);
        $response->assertSee('07, 12', false);
        $response->assertSee('data-rooms="Room 07, 12"', false);
    }

    public function test_billing_process_modal_formats_single_digit_room_number(): void
    {
        $guest = Guest::create([
            'fname' => 'Marco',
            'lname' => 'Cruz',
            'contact_no' => '09181234567',
            'email' => 'marco@example.com',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => ['4'],
            'stay_type' => 'overnight',
            'nights' => 1,
            'check_in_datetime' => now()->subHour(),
            'check_out_datetime' => now()->addDay(),
            'status' => 'checked_in',
            'total_amount' => 2200.00,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 2200.00,
            'extra_charges_total' => 0,
            'grand_total' => 2200.00,
            'status' => 'unpaid',
        ]);

        $response = $this->get(route('content.billing', ['edit_id' => $invoice->id]));

        $response->assertOk();
        $response->assertSee('04', false);
    }
}
