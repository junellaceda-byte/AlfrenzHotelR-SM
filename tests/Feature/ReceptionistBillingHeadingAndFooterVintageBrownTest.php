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

class ReceptionistBillingHeadingAndFooterVintageBrownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_billing_heading_areas_and_footer_backgrounds_are_vintage_brown(): void
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
            'check_in_datetime' => Carbon::now()->subHours(3),
            'check_out_datetime' => Carbon::now()->addHours(1),
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

        // 1. Heading areas have Vintage Brown #443627 background and readable text
        $response->assertSee('card-header', false);
        $response->assertSee('Pending Payments');
        $response->assertSee('Confirmed Payments');
        $response->assertSee('Payments that are due or not yet settled.');
        $response->assertSee('Successfully processed and settled payments.');

        // 2. Footer areas have Vintage Brown #443627 background and readable text
        $response->assertSee('card-footer', false);
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('color: #F5F7DF', false);
        $response->assertSee('Showing 1 to 1 of 1 result(s)');

        // 3. Table headers preserve Vintage Brown #6A4435
        $response->assertSee('background-color: #6A4435', false);
        $response->assertSee('Due Date');
        $response->assertSee('Date Paid');

        // 4. Data rows preserve Beige Green #F2F6D0
        $response->assertSee('background-color: #F2F6D0', false);

        // 5. Buttons and functionality are preserved
        $response->assertSee('Pay Now');
        $response->assertSee('View Payment Details');
    }
}
