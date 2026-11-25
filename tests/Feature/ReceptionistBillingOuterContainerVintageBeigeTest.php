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

class ReceptionistBillingOuterContainerVintageBeigeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_billing_outer_table_containers_are_vintage_beige(): void
    {
        $room = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'occupied',
        ]);

        $guest = Guest::create([
            'fname' => 'Roberto',
            'lname' => 'Gomez',
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

        // 1. Outer containers of both tables have Vintage Beige #BF9B7A
        $response->assertSee('billing-card-container', false);
        $response->assertSee('billing-table-container', false);
        $response->assertSee('background-color: #BF9B7A', false);

        // 2. Existing header and footer backgrounds remain Vintage Brown #443627
        $response->assertSee('card-header', false);
        $response->assertSee('card-footer', false);
        $response->assertSee('background-color: #443627', false);

        // 3. Existing table header remains Vintage Brown #6A4435
        $response->assertSee('background-color: #6A4435', false);

        // 4. Existing data rows remain Beige Green #F2F6D0
        $response->assertSee('background-color: #F2F6D0', false);

        // 5. Text readability, pagination, action buttons preserved
        $response->assertSee('Pending Payments');
        $response->assertSee('Confirmed Payments');
        $response->assertSee('Showing 1 to 1 of 1 result(s)');
        $response->assertSee('Pay Now');
        $response->assertSee('View Payment Details');
    }
}
