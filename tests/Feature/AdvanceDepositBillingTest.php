<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Charge;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceDepositBillingTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();

        $this->room = Room::create([
            'room_number' => '07',
            'short_stay_rate' => 800.00,
            'overnight_rate' => 2500.00,
            'status' => 'available',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Elena',
            'lname' => 'Reyes',
            'phone' => '09181234567',
        ]);
    }

    public function test_advance_deposit_from_reservation_carries_over_to_booking_upon_check_in(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $reservation = Reservation::create([
            'guest_name' => 'Elena Reyes',
            'guest_id' => $this->guest->id,
            'phone_number' => '09181234567',
            'reservation_start_date' => $now->copy(),
            'reservation_end_date' => $now->copy()->addHours(24),
            'number_of_rooms' => 1,
            'selected_rooms' => [$this->room->id],
            'guests' => 1,
            'status' => 'reserved',
            'stay_type' => 'overnight',
            'deposit' => 1000.00,
            'payment_method' => 'cash',
        ]);

        $response = $this->post(route('reservation.checkIn', $reservation->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reservation->refresh();
        $this->assertEquals('checked_in', $reservation->status);

        $booking = Booking::where('guest_id', $this->guest->id)->latest('id')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(1000.00, (float) $booking->deposit);
        $this->assertEquals(1000.00, (float) $booking->advance_deposit);

        $invoice = $booking->invoice;
        $this->assertNotNull($invoice);
        $this->assertEquals(1000.00, (float) $invoice->advance_deposit);
        // Room charge 1800 - deposit 1000 = remaining balance 800
        $this->assertEquals(800.00, (float) $invoice->remaining_balance);
    }

    public function test_billing_process_modal_displays_advance_deposit_and_deducts_from_remaining_balance(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'phone' => '09181234567',
            'check_in_datetime' => $now->copy()->subHours(1),
            'check_out_datetime' => $now->copy()->addHours(23),
            'booked_rooms' => [$this->room->id],
            'stay_type' => 'overnight',
            'deposit' => 1000.00,
            'status' => 'checked_in',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room 07 - 24-hour stay',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'amount' => 2500.00,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-DEP',
            'room_charges' => 2500.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 2500.00,
            'status' => 'unpaid',
            'due_date' => $now->toDateString(),
        ]);

        // Open billing page with the modal auto-triggered via edit_id
        $response = $this->get(route('content.billing', ['edit_id' => $invoice->id]));
        $response->assertOk();

        // 1. Under Charges & Add-ons: Advance Deposit entry immediately after Room Rate
        $response->assertSee('Room Rate');
        $response->assertSee('Advance Deposit');
        $response->assertSee('billing_advance_deposit_entry', false);
        $response->assertSee('- ₱ 1,000.00');

        // Verify subtotal is NOT increased by the deposit (credited once, not an extra charge)
        $response->assertSee('Subtotal');
        $response->assertSee('₱ 2,500.00');

        // 2. Under Summary: Advance Deposit displayed and deducted from remaining balance due
        $response->assertSee('Summary');
        $response->assertSee('Remaining Balance Due');
        $response->assertSee('₱ 1,500.00');
    }

    public function test_confirmed_payments_modal_displays_advance_deposit_and_remaining_payment(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'phone' => '09181234567',
            'check_in_datetime' => $now->copy()->subHours(2),
            'check_out_datetime' => $now->copy()->subHour(),
            'booked_rooms' => [$this->room->id],
            'stay_type' => 'overnight',
            'deposit' => 800.00,
            'status' => 'checked_out',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room 07 - 24-hour stay',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'amount' => 2500.00,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-CONFIRMED-DEP',
            'room_charges' => 2500.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 2500.00,
            'status' => 'paid',
            'due_date' => $now->toDateString(),
            'updated_at' => $now,
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2500.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now,
        ]);

        $response = $this->get(route('content.billing'));
        $response->assertOk();

        // Check data attributes on the view details button
        $response->assertSee('data-advance-deposit="800.00"', false);
        $response->assertSee('data-remaining-payment="1,700.00"', false);
        $response->assertSee('data-grand-total="2,500.00"', false);

        // Check modal breakdown elements for advance deposit and remaining payment
        $response->assertSee('id="pd_deposit_row"', false);
        $response->assertSee('id="pd_advance_deposit"', false);
        $response->assertSee('id="pd_remaining_row"', false);
        $response->assertSee('id="pd_remaining_payment"', false);
        $response->assertSee('id="pd_deposit_info_col"', false);
        $response->assertSee('id="pd_remaining_info_col"', false);
    }

    public function test_billing_without_advance_deposit_displays_standard_total_amount(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'phone' => '09181234567',
            'check_in_datetime' => $now->copy()->subHours(1),
            'check_out_datetime' => $now->copy()->addHours(23),
            'booked_rooms' => [$this->room->id],
            'stay_type' => 'overnight',
            'deposit' => 0.00,
            'status' => 'checked_in',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room 07 - 24-hour stay',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'amount' => 2500.00,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-NO-DEP',
            'room_charges' => 2500.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 2500.00,
            'status' => 'unpaid',
            'due_date' => $now->toDateString(),
        ]);

        $response = $this->get(route('content.billing', ['edit_id' => $invoice->id]));
        $response->assertOk();

        // When no deposit exists, the advance deposit entry is not rendered
        $response->assertDontSee('billing_advance_deposit_entry', false);
        $response->assertDontSee('Remaining Balance Due');
        $response->assertSee('Total Amount');
        $response->assertSee('₱ 2,500.00');
    }
}
