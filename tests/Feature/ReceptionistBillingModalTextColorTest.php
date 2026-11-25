<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistBillingModalTextColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_billing_modal_text_colors_are_black_while_header_and_buttons_are_preserved(): void
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

        // 1. Verify modal header retains text-white and close button
        $response->assertSee('modal-header text-white', false);
        $response->assertSee('btn-close btn-close-white', false);
        $response->assertSee('Billing Process - Elena Reyes', false);

        // 2. Verify modal body and elements have color: #000000
        $response->assertSee('style="background-color: #EFEABB; color: #000000;"', false);
        $response->assertSee('style="color: #000000;"', false);
        $response->assertSee('Guest & Room Information', false);
        $response->assertSee('Charges & Add-ons', false);
        $response->assertSee('Payment & Summary', false);

        // 3. Verify CSS rules enforce black text while excluding buttons and badges
        $response->assertSee('#billingModal .modal-body,', false);
        $response->assertSee('#billingModal .modal-body *:not(.btn):not(.btn *):not(.badge) {', false);
        $response->assertSee('color: #000000 !important;', false);

        // 4. Verify button styling and button text are preserved
        $response->assertSee('Process Payment', false);
        $response->assertSee('btn text-white w-100 py-2 fw-semibold', false);
        $response->assertSee('btn btn-outline-secondary', false);
        $response->assertSee('Cash', false);
        $response->assertSee('E-Wallet', false);

        // 5. Verify existing backgrounds are preserved
        $response->assertSee('background-color: #EFF5CD', false);
        $response->assertSee('background-color: #E7E6B9', false);
    }
}
