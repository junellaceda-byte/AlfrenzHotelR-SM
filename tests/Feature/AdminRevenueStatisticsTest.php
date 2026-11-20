<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRevenueStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room1 = Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Elena',
            'lname' => 'Reyes',
            'contact_no' => '09123456789',
            'email' => 'elena@example.com',
        ]);
    }

    public function test_revenue_stats_endpoint_returns_valid_structure(): void
    {
        $response = $this->getJson(route('api.admin.revenue-stats'));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'revenue_chart' => [
                'today' => ['labels', 'data'],
                'weekly' => ['labels', 'data'],
                'monthly' => ['labels', 'data'],
                'yearly' => ['labels', 'data'],
            ],
            'server_time',
        ]);

        $chart = $response->json('revenue_chart');
        $this->assertEquals(['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM'], $chart['today']['labels']);
        $this->assertEquals(['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'], $chart['weekly']['labels']);
        $this->assertCount(12, $chart['monthly']['labels']);
        $this->assertCount(6, $chart['yearly']['labels']);
    }

    public function test_revenue_stats_reflects_confirmed_payments_from_receptionist_billing(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $currentMonth = (int) $now->format('n');
        $currentDay = (int) $now->format('j');

        // Create booking
        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'booked_rooms' => [$this->room1->id],
            'stay_type' => 'overnight',
            'status' => 'checked_out',
            'check_in_datetime' => $now->copy()->subHours(5),
            'check_out_datetime' => $now,
            'actual_check_out' => $now,
        ]);

        // Create invoice settled at Receptionist Billing
        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 2200.00,
            'extra_charges_total' => 300.00,
            'discount_amount' => 0.00,
            'grand_total' => 2500.00,
            'status' => 'paid',
            'updated_at' => $now->copy()->startOfDay()->addHours(14)->addMinutes(30), // 2:30 PM
        ]);

        // Create PaymentLog recorded at check-out
        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2500.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now->copy()->startOfDay()->addHours(14)->addMinutes(30), // 2:30 PM (2 PM slot)
            'created_at' => $now->copy()->startOfDay()->addHours(14)->addMinutes(30),
        ]);

        $response = $this->getJson(route('api.admin.revenue-stats'));
        $response->assertOk();

        $chart = $response->json('revenue_chart');

        // 1. Today check: 2:30 PM falls in slot index 3 ('2 PM')
        $this->assertEquals(2500.00, $chart['today']['data'][3]);

        // 2. Weekly check: Week index matching current day
        $weekIndex = match (true) {
            $currentDay <= 7 => 0,
            $currentDay <= 14 => 1,
            $currentDay <= 21 => 2,
            $currentDay <= 28 => 3,
            default => 4,
        };
        $this->assertEquals(2500.00, $chart['weekly']['data'][$weekIndex]);

        // 3. Monthly check: Current month index (0-based)
        $this->assertEquals(2500.00, $chart['monthly']['data'][$currentMonth - 1]);

        // 4. Yearly check: Current year index (last item in 6-year window)
        $this->assertEquals(2500.00, end($chart['yearly']['data']));
    }

    public function test_admin_overview_stats_endpoint_includes_live_revenue_chart(): void
    {
        $response = $this->getJson(route('api.admin.overview-stats'));

        $response->assertOk();
        $response->assertJsonStructure([
            'occupancy_today',
            'total_rooms',
            'revenue_today',
            'revenue_today_formatted',
            'checkins_today',
            'checkouts_today',
            'checkins_list',
            'checkouts_list',
            'revenue_chart' => [
                'today',
                'weekly',
                'monthly',
                'yearly',
            ],
        ]);
    }
}
