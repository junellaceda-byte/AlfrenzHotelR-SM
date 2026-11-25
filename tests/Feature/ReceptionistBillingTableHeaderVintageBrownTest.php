<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistBillingTableHeaderVintageBrownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_pending_and_confirmed_payment_table_headers_are_vintage_brown(): void
    {
        $room = Room::create([
            'room_number' => '101',
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

        // Verify Pending Payments table and Confirmed Payments table headers have Vintage Brown #6A4435 background
        $response->assertSee('billing-pending-table', false);
        $response->assertSee('billing-confirmed-table', false);
        $response->assertSee('billing-table-header-vintage-brown', false);
        $response->assertSee('background-color: #6A4435', false);
        $response->assertSee('--bs-table-bg: #6A4435', false);
        $response->assertSee('color: #ffffff', false);

        // Verify table headers preserve their exact class and labels
        $response->assertSee('<th class="col-total-amount text-nowrap">Total Amount</th>', false);
        $response->assertSee('<th>Due Date</th>', false);
        $response->assertSee('<th>Date Paid</th>', false);
        $response->assertSee('<th>Guest Name</th>', false);
        $response->assertSee('<th>Status</th>', false);
        $response->assertSee('<th class="text-center">Action</th>', false);
    }
}
