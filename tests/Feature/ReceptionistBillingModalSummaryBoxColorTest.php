<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistBillingModalSummaryBoxColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_billing_modal_summary_box_has_eff5cd_background(): void
    {
        $room = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'occupied',
        ]);

        $guest = Guest::create([
            'fname' => 'Elena',
            'lname' => 'Reyes',
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

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 500.00,
            'extra_charges_total' => 150.00,
            'grand_total' => 650.00,
            'status' => 'unpaid',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->get(route('content.billing', ['edit_id' => $invoice->id]));

        $response->assertOk();

        // 1. Verify Summary box has background-color: #EFF5CD
        $response->assertSee('billing-summary-box', false);
        $response->assertSee('background-color: #EFF5CD', false);

        // 2. Verify Summary contents are intact
        $response->assertSee('Summary');
        $response->assertSee('Room Charge');
        $response->assertSee('Incidentals');
        $response->assertSee('Total Amount');
        $response->assertSee('₱ 500.00');
        $response->assertSee('₱ 150.00');
        $response->assertSee('₱ 650.00');

        // 3. Verify Payment & Summary card and modal are present
        $response->assertSee('Payment & Summary', false);
        $response->assertSee('billingModal');
    }
}
