<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverviewCardsTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Room $room2;

    private Room $room3;

    private Room $room4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room1 = Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'occupied',
        ]);

        $this->room2 = Room::create([
            'room_number' => '02',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'occupied',
        ]);

        $this->room3 = Room::create([
            'room_number' => '03',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'reserved',
        ]);

        $this->room4 = Room::create([
            'room_number' => '04',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);
    }

    public function test_admin_overview_cards_display_accurate_today_data(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        // Guest 1: Direct booking checked in today
        $guest1 = Guest::create(['fname' => 'Alice', 'lname' => 'Smith', 'phone' => '09111111111']);
        $booking1 = Booking::create([
            'guest_id' => $guest1->id,
            'phone' => '09111111111',
            'check_in_datetime' => $now->copy()->startOfDay()->addHours(8),
            'check_out_datetime' => $now->copy()->startOfDay()->addHours(20),
            'booked_rooms' => [$this->room1->id],
            'status' => 'checked_in',
            'created_at' => $now->copy()->startOfDay()->addHours(8),
        ]);

        // Guest 2: Reservation checked in today
        $guest2 = Guest::create(['fname' => 'Bob', 'lname' => 'Jones', 'phone' => '09222222222']);
        $booking2 = Booking::create([
            'guest_id' => $guest2->id,
            'phone' => '09222222222',
            'check_in_datetime' => $now->copy()->startOfDay()->addHours(10),
            'check_out_datetime' => $now->copy()->startOfDay()->addHours(22),
            'booked_rooms' => [$this->room2->id],
            'status' => 'checked_in',
            'created_at' => $now->copy()->startOfDay()->addHours(10),
        ]);
        Reservation::create([
            'guest_id' => $guest2->id,
            'guest_name' => 'Bob Jones',
            'phone_number' => '09222222222',
            'reservation_start_date' => $now->copy()->startOfDay()->addHours(10),
            'reservation_end_date' => $now->copy()->startOfDay()->addHours(22),
            'selected_rooms' => [$this->room2->id],
            'number_of_rooms' => 1,
            'status' => 'checked_in', // Already checked in
        ]);

        // Guest 3: Reservation STILL MARKED AS RESERVED (not yet checked in) - MUST BE EXCLUDED from check-ins!
        $guest3 = Guest::create(['fname' => 'Charlie', 'lname' => 'Brown', 'phone' => '09333333333']);
        Reservation::create([
            'guest_id' => $guest3->id,
            'guest_name' => 'Charlie Brown',
            'phone_number' => '09333333333',
            'reservation_start_date' => $now->copy()->startOfDay()->addHours(14),
            'reservation_end_date' => $now->copy()->startOfDay()->addHours(18),
            'selected_rooms' => [$this->room3->id],
            'number_of_rooms' => 1,
            'status' => 'reserved', // Still reserved!
        ]);

        // Guest 4: Checked out TODAY
        $guest4 = Guest::create(['fname' => 'David', 'lname' => 'Miller', 'phone' => '09444444444']);
        $booking4 = Booking::create([
            'guest_id' => $guest4->id,
            'phone' => '09444444444',
            'check_in_datetime' => $now->copy()->startOfDay()->addHours(6),
            'check_out_datetime' => $now->copy()->startOfDay()->addHours(11),
            'booked_rooms' => [$this->room4->id],
            'status' => 'checked_out',
            'actual_check_out' => $now->copy()->startOfDay()->addHours(11),
            'created_at' => $now->copy()->startOfDay()->addHours(6),
        ]);

        // Guest 5: Checked out YESTERDAY (must NOT be counted today)
        $guest5 = Guest::create(['fname' => 'Eve', 'lname' => 'Davis', 'phone' => '09555555555']);
        $booking5 = Booking::create([
            'guest_id' => $guest5->id,
            'phone' => '09555555555',
            'check_in_datetime' => Carbon::parse($yesterday.' 08:00:00'),
            'check_out_datetime' => Carbon::parse($yesterday.' 12:00:00'),
            'booked_rooms' => [$this->room1->id],
            'status' => 'checked_out',
            'actual_check_out' => Carbon::parse($yesterday.' 12:00:00'),
            'created_at' => Carbon::parse($yesterday.' 08:00:00'),
        ]);

        // Payments:
        // Invoice 1: Paid TODAY for booking 4
        $invoice1 = Invoice::create([
            'booking_id' => $booking4->id,
            'guest_id' => $guest4->id,
            'status' => 'paid',
            'grand_total' => 1250.50,
            'created_at' => $now->copy()->startOfDay()->addHours(11),
            'updated_at' => $now->copy()->startOfDay()->addHours(11),
        ]);
        PaymentLog::create([
            'invoice_id' => $invoice1->id,
            'amount_paid' => 1250.50,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now->copy()->startOfDay()->addHours(11),
            'created_at' => $now->copy()->startOfDay()->addHours(11),
        ]);

        // Invoice 2: Paid YESTERDAY (must NOT be counted in revenue today)
        $invoice2 = Invoice::create([
            'booking_id' => $booking5->id,
            'guest_id' => $guest5->id,
            'status' => 'paid',
            'grand_total' => 3000.00,
            'created_at' => Carbon::parse($yesterday.' 12:00:00'),
            'updated_at' => Carbon::parse($yesterday.' 12:00:00'),
        ]);
        PaymentLog::create([
            'invoice_id' => $invoice2->id,
            'amount_paid' => 3000.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => Carbon::parse($yesterday.' 12:00:00'),
            'created_at' => Carbon::parse($yesterday.' 12:00:00'),
        ]);

        // Request Administrator Overview
        $response = $this->get('/admindashboard?section=overview');
        $response->assertOk();

        // 1. Today's Occupancy: 3 rooms (room1, room2 active checked_in + room4 booked today)
        $response->assertViewHas('occupancyToday', 3);
        $response->assertViewHas('totalRooms', 4);

        // 2. Revenue Today: 1250.50 (excluding yesterday's 3000)
        $response->assertViewHas('revenueToday', 1250.50);

        // 3. Check-ins Today: 3 guests who checked in today (Alice, Bob, David), excluding Charlie (still reserved)
        $response->assertViewHas('checkInsToday', 3);

        // 4. Check-outs Today: 1 guest who checked out today (David), excluding Eve (yesterday)
        $response->assertViewHas('checkOutsToday', 1);

        // Verify HTML output contains rendered values
        $response->assertSee('₱1,250.50');
        $response->assertSee('of <span id="admin-total-rooms">4</span> rooms', false);
    }

    public function test_admin_overview_api_endpoint_returns_accurate_realtime_json(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $guest = Guest::create(['fname' => 'Grace', 'lname' => 'Hopper', 'phone' => '09999999999']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09999999999',
            'check_in_datetime' => $now,
            'check_out_datetime' => $now->copy()->addHours(6),
            'booked_rooms' => [$this->room1->id],
            'status' => 'checked_in',
            'created_at' => $now,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'guest_id' => $guest->id,
            'status' => 'paid',
            'grand_total' => 850.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 850.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now,
            'created_at' => $now,
        ]);

        $response = $this->getJson(route('api.admin.overview-stats'));

        $response->assertOk();
        $response->assertJson([
            'occupancy_today' => 1,
            'total_rooms' => 4,
            'revenue_today' => 850.00,
            'revenue_today_formatted' => '₱850.00',
            'checkins_today' => 1,
            'checkouts_today' => 0,
        ]);
        $response->assertJsonStructure([
            'occupancy_today',
            'total_rooms',
            'revenue_today',
            'revenue_today_formatted',
            'checkins_today',
            'checkouts_today',
            'checkins_list',
            'checkouts_list',
        ]);
    }
}
