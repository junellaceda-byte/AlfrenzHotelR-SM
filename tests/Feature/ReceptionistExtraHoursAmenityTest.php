<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Charge;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistExtraHoursAmenityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $staff = Staff::create([
            'first_name' => 'Receptionist',
            'last_name' => 'User',
            'username' => 'receptionist_test',
            'password' => 'secret123',
            'contact_number' => '09123456789',
            'is_online' => true,
        ]);

        session([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'receptionist_last_activity' => now()->timestamp,
        ]);
    }

    public function test_booking_history_shows_add_extra_hours_modal(): void
    {
        $guest = Guest::create([
            'fname' => 'Maria',
            'lname' => 'Santos',
            'phone' => '09123456789',
        ]);

        $room = Room::create([
            'room_number' => 101,
            'room_type' => 'Standard',
            'status' => 'occupied',
            'short_stay_rate' => 500,
            'overnight_rate' => 1200,
        ]);

        $checkIn = Carbon::now()->subHours(2);
        $checkOut = Carbon::now()->addHours(2);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [$room->id],
            'guests' => 1,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 4,
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'status' => 'checked_in',
            'deposit' => 0,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 500,
            'extra_charges_total' => 0,
            'discount_amount' => 0,
            'grand_total' => 500,
            'status' => 'unpaid',
        ]);

        $response = $this->get(route('booking.index'));

        $response->assertStatus(200);
        $response->assertSee('ADD EXTRA HOURS');
        $response->assertSee('btn-add-extra-hours');
        $response->assertSee('aeh_btn_minus');
        $response->assertSee('aeh_btn_plus');
        $response->assertSee('150');
        $response->assertSee('aehChangeHours');
    }

    public function test_can_add_one_extra_hour_in_single_increment(): void
    {
        $guest = Guest::create([
            'fname' => 'Coco',
            'lname' => 'Martin',
            'phone' => '09123456788',
        ]);

        $room = Room::create([
            'room_number' => 104,
            'room_type' => 'Standard',
            'status' => 'occupied',
            'short_stay_rate' => 500,
            'overnight_rate' => 1200,
        ]);

        $checkIn = Carbon::parse('2026-11-26 10:00:00');
        $checkOut = Carbon::parse('2026-11-26 13:00:00');

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [$room->id],
            'guests' => 1,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'status' => 'checked_in',
            'deposit' => 0,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 500,
            'extra_charges_total' => 0,
            'discount_amount' => 0,
            'grand_total' => 500,
            'status' => 'unpaid',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room Accommodation (3 Hours)',
            'quantity' => 1,
            'unit_price' => 500,
            'amount' => 500,
            'is_paid' => false,
        ]);

        // Submit exactly 1 extra hour (1 * 150 = 150)
        $response = $this->post(route('booking.addExtraHours', $booking->id), [
            'extra_hours' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $booking->refresh();
        $invoice->refresh();

        // 1. Checkout extended by exactly 1 hour (from 13:00 to 14:00)
        $this->assertEquals('2026-11-26 14:00:00', Carbon::parse($booking->check_out_datetime)->format('Y-m-d H:i:s'));

        // 2. Stay duration extended from 3 to 4 hours
        $this->assertEquals(4, $booking->short_stay_hours);

        // 3. Charge record is exactly 1 hour at 150.00
        $charge = Charge::where('booking_id', $booking->id)->where('type', 'extra_hours')->first();
        $this->assertNotNull($charge);
        $this->assertEquals(1, $charge->quantity);
        $this->assertEquals(150.00, (float) $charge->unit_price);
        $this->assertEquals(150.00, (float) $charge->amount);
        $this->assertEquals('Extra Hours (1 hr)', $charge->description);

        // 4. Invoice updated by exactly 150.00
        $this->assertEquals(150.00, (float) $invoice->extra_charges_total);
        $this->assertEquals(650.00, (float) $invoice->grand_total);
    }

    public function test_can_add_extra_hours_extends_checkout_and_billing_balance(): void
    {
        $guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'phone' => '09123456780',
        ]);

        $room = Room::create([
            'room_number' => 102,
            'room_type' => 'Standard',
            'status' => 'occupied',
            'short_stay_rate' => 600,
            'overnight_rate' => 1500,
        ]);

        $checkIn = Carbon::parse('2026-11-26 10:00:00');
        $checkOut = Carbon::parse('2026-11-26 14:00:00');

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [$room->id],
            'guests' => 1,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 4,
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'status' => 'checked_in',
            'deposit' => 0,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 600,
            'extra_charges_total' => 0,
            'discount_amount' => 0,
            'grand_total' => 600,
            'status' => 'unpaid',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room Accommodation (4 Hours)',
            'quantity' => 1,
            'unit_price' => 600,
            'amount' => 600,
            'is_paid' => false,
        ]);

        // Submit 2 extra hours (2 * 150 = 300)
        $response = $this->post(route('booking.addExtraHours', $booking->id), [
            'extra_hours' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $booking->refresh();
        $invoice->refresh();

        // 1. Check checkout extended by 2 hours (from 14:00 to 16:00)
        $this->assertEquals('2026-11-26 16:00:00', Carbon::parse($booking->check_out_datetime)->format('Y-m-d H:i:s'));

        // 2. Check stay duration extended
        $this->assertEquals(6, $booking->short_stay_hours);

        // 3. Check Charge created with rate 150/hr
        $charge = Charge::where('booking_id', $booking->id)->where('type', 'extra_hours')->first();
        $this->assertNotNull($charge);
        $this->assertEquals(2, $charge->quantity);
        $this->assertEquals(150.00, (float) $charge->unit_price);
        $this->assertEquals(300.00, (float) $charge->amount);

        // 4. Check Invoice total updated (600 room + 300 extra = 900)
        $this->assertEquals(300.00, (float) $invoice->extra_charges_total);
        $this->assertEquals(900.00, (float) $invoice->grand_total);
    }

    public function test_extra_hours_displayed_in_billing_process_and_confirmed_payment_details(): void
    {
        $guest = Guest::create([
            'fname' => 'Pedro',
            'lname' => 'Penduko',
            'phone' => '09123456781',
        ]);

        $room = Room::create([
            'room_number' => 103,
            'room_type' => 'Deluxe',
            'status' => 'occupied',
            'short_stay_rate' => 700,
            'overnight_rate' => 1800,
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [$room->id],
            'guests' => 1,
            'stay_type' => 'overnight',
            'check_in_datetime' => Carbon::parse('2026-11-26 12:00:00'),
            'check_out_datetime' => Carbon::parse('2026-11-27 12:00:00'),
            'status' => 'checked_in',
            'deposit' => 0,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 1800,
            'extra_charges_total' => 0,
            'discount_amount' => 0,
            'grand_total' => 1800,
            'status' => 'unpaid',
        ]);

        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'room',
            'description' => 'Room Accommodation',
            'quantity' => 1,
            'unit_price' => 1800,
            'amount' => 1800,
            'is_paid' => false,
        ]);

        // Add 3 extra hours (3 * 150 = 450)
        $this->post(route('booking.addExtraHours', $booking->id), [
            'extra_hours' => 3,
        ]);

        // Check Billing Process view (edit_id)
        $billingResponse = $this->get(route('content.billing', ['edit_id' => $invoice->id]));
        $billingResponse->assertStatus(200);
        $billingResponse->assertSee('Extra Hours (3 hrs)');
        $billingResponse->assertSee('450.00');

        // Now simulate payment settlement
        $payResponse = $this->post(route('billing.processPayment', $invoice->id), [
            'payment_method' => 'cash',
        ]);
        $payResponse->assertRedirect(route('content.billing'));

        // Check Confirmed Payments view
        $confirmedResponse = $this->get(route('content.billing'));
        $confirmedResponse->assertStatus(200);
        $confirmedResponse->assertSee('Extra Hours (3 hrs)');
        $confirmedResponse->assertSee('450');
    }
}
