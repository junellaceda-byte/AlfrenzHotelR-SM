<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingStayTypeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $staff = Staff::create([
            'first_name' => 'Receptionist',
            'last_name' => 'User',
            'username' => 'receptionist_test',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'contact' => '09123456789',
        ]);

        $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
        ]);
    }

    public function test_booking_model_stay_type_display_attribute(): void
    {
        $guest = Guest::create([
            'fname' => 'Jane',
            'lname' => 'Doe',
            'email' => 'jane@example.com',
            'contactno' => '09111111111',
        ]);

        $shortBooking3 = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addHours(3),
            'booked_rooms' => ['101'],
        ]);
        $this->assertEquals('3 Hours', $shortBooking3->stay_type_display);

        $shortBooking6 = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addHours(6),
            'booked_rooms' => ['102'],
        ]);
        $this->assertEquals('6 Hours', $shortBooking6->stay_type_display);

        $shortBooking12 = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 12,
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addHours(12),
            'booked_rooms' => ['103'],
        ]);
        $this->assertEquals('12 Hours', $shortBooking12->stay_type_display);

        $overnightBooking = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addDay(),
            'booked_rooms' => ['104'],
        ]);
        $this->assertEquals('24 Hours', $overnightBooking->stay_type_display);
    }

    public function test_billing_modal_displays_stay_type_instead_of_nights(): void
    {
        $guest = Guest::create([
            'fname' => 'John',
            'lname' => 'Smith',
            'email' => 'johnsmith@example.com',
            'contactno' => '09222222222',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addHours(6),
            'booked_rooms' => ['105'],
            'status' => 'checked_in',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 1200,
            'extra_charges_total' => 0,
            'grand_total' => 1200,
            'status' => 'unpaid',
        ]);

        $response = $this->get('/billing?edit_id='.$invoice->id);

        $response->assertStatus(200);
        $response->assertSee('Stay Type');
        $response->assertSee('6 Hours');
        $response->assertDontSee('nights</label>', false);
        $response->assertDontSee('N/A nights');
        $response->assertDontSee('Quick Add Amenities');
        $response->assertDontSee('id="quickAddAmenitiesList"', false);
    }

    public function test_billing_modal_displays_24_hours_for_overnight_stay(): void
    {
        $guest = Guest::create([
            'fname' => 'Alice',
            'lname' => 'Wonder',
            'email' => 'alice@example.com',
            'contactno' => '09333333333',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'check_in_datetime' => now(),
            'check_out_datetime' => now()->addDay(),
            'booked_rooms' => ['106'],
            'status' => 'checked_in',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 2500,
            'extra_charges_total' => 0,
            'grand_total' => 2500,
            'status' => 'unpaid',
        ]);

        $response = $this->get('/billing?edit_id='.$invoice->id);

        $response->assertStatus(200);
        $response->assertSee('Stay Type');
        $response->assertSee('24 Hours');
    }
}
