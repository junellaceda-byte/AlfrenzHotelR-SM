<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create([
            'room_number' => '05',
            'short_stay_rate' => 500.00,
            'overnight_rate' => 2000.00,
            'status' => 'available',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'contact_no' => '09123456789',
            'email' => 'juan@example.com',
        ]);
    }

    public function test_admin_notifications_endpoint_returns_expected_structure(): void
    {
        $response = $this->getJson(route('api.admin.notifications'));

        $response->assertOk();
        $response->assertJsonStructure([
            'due_guests',
            'cancellations',
            'cancellations_count',
            'lost_revenue_total',
            'lost_revenue_total_formatted',
            'server_time',
        ]);
        $response->assertJson([
            'cancellations_count' => 0,
            'lost_revenue_total' => 0,
        ]);
    }

    public function test_cancelled_reservations_appear_in_cancellations(): void
    {
        Reservation::create([
            'guest_name' => 'Maria Clara',
            'phone_number' => '09988776655',
            'email' => 'maria@example.com',
            'number_of_rooms' => 1,
            'selected_rooms' => [$this->room->id],
            'reservation_start_date' => now()->addDays(2),
            'reservation_end_date' => now()->addDays(4),
            'deposit' => 750.00,
            'status' => 'cancelled',
        ]);

        $response = $this->getJson(route('api.admin.notifications'));

        $response->assertOk();
        $this->assertSame(1, $response->json('cancellations_count'));
        $this->assertEquals(750.00, $response->json('lost_revenue_total'));
        $this->assertSame('Maria Clara', $response->json('cancellations.0.guest_name'));
        $this->assertSame('Reservation', $response->json('cancellations.0.type'));
        $this->assertSame('₱750.00', $response->json('cancellations.0.deposit_formatted'));
        $this->assertNotEmpty($response->json('cancellations.0.booked_rooms_details'));
        $this->assertStringContainsString('RM', $response->json('cancellations.0.booked_rooms_details'));
    }

    public function test_cancelled_bookings_appear_in_cancellations(): void
    {
        Booking::create([
            'guest_id' => $this->guest->id,
            'check_in_datetime' => now()->subDay(),
            'check_out_datetime' => now()->addDay(),
            'stay_type' => 'overnight',
            'booked_rooms' => [$this->room->id],
            'total_amount' => 2000.00,
            'deposit' => 1000.00,
            'status' => 'cancelled',
        ]);

        $response = $this->getJson(route('api.admin.notifications'));

        $response->assertOk();
        $this->assertSame(1, $response->json('cancellations_count'));
        $this->assertEquals(1000.00, $response->json('lost_revenue_total'));
        $this->assertSame('Juan Dela Cruz', $response->json('cancellations.0.guest_name'));
        $this->assertSame('Booking', $response->json('cancellations.0.type'));
        $this->assertSame('₱1,000.00', $response->json('cancellations.0.deposit_formatted'));
    }

    public function test_due_guests_appear_in_notifications_when_checkout_within_30_minutes(): void
    {
        $now = Carbon::parse('2026-11-20 12:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'check_in_datetime' => $now->copy()->subHours(2),
            'check_out_datetime' => $now->copy()->addMinutes(20),
            'stay_type' => 'short_stay',
            'booked_rooms' => [$this->room->id],
            'total_amount' => 500.00,
            'deposit' => 500.00,
            'status' => 'checked_in',
        ]);

        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => $now->toDateString(),
            'due_date' => $now->toDateString(),
            'total_room_charges' => 500.00,
            'total_amenity_charges' => 0.00,
            'grand_total' => 500.00,
            'status' => 'unpaid',
        ]);

        $response = $this->getJson(route('api.admin.notifications'));

        $response->assertOk();
        $this->assertCount(1, $response->json('due_guests'));
        $this->assertSame('Juan Dela Cruz', $response->json('due_guests.0.name'));
        $this->assertSame('05', $response->json('due_guests.0.room_number'));
        $this->assertFalse($response->json('due_guests.0.is_overdue'));
        $this->assertSame(20, $response->json('due_guests.0.minutes'));
        $this->assertNotEmpty($response->json('due_guests.0.booked_rooms_details'));
        $this->assertStringContainsString('RM', $response->json('due_guests.0.booked_rooms_details'));
        $this->assertSame('Short Stay', $response->json('due_guests.0.stay_type'));

        Carbon::setTestNow();
    }
}
