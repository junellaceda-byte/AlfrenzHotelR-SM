<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistTableVintageBrownHeaderFooterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'SystemSettingSeeder']);
    }

    public function test_reservation_table_header_and_footer_are_vintage_brown(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

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

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('reservation.index'));

        $response->assertOk();

        // Check Table Header is styled with Vintage Brown #443627
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('<thead style="background-color: #443627;', false);

        // Check Table Footer (card-footer) is styled with Vintage Brown #443627 and Beige text
        $response->assertSee('card-footer', false);
        $response->assertSee('style="background-color: #443627; color: #F5F7DF;"', false);
    }

    public function test_booking_history_table_header_and_footer_are_vintage_brown(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $room = Room::create([
            'room_number' => '102',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'available',
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

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('booking.index'));

        $response->assertOk();

        // Check Table Header is styled with Vintage Brown #443627
        $response->assertSee('<thead style="background-color: #443627;', false);

        // Check Table Footer (card-footer) is styled with Vintage Brown #443627 and Beige text
        $response->assertSee('style="background-color: #443627; color: #F5F7DF;"', false);
    }
}
