<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRealtimeSyncTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Room $room2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room1 = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $this->room2 = Room::create([
            'room_number' => '102',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);
    }

    public function test_admin_overview_and_rooms_sync_in_real_time_when_booking_created(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $today = $now->toDateString();

        // 1. Initial State: No checkins today, occupancy 0
        $resBefore = $this->getJson('/api/admin/overview-stats');
        $resBefore->assertOk()
            ->assertJson([
                'occupancy_today' => 0,
                'checkins_today' => 0,
            ]);

        $roomsBefore = $this->getJson('/api/dashboard/notifications');
        $roomsBefore->assertOk()
            ->assertJsonPath('kpi.available', 2)
            ->assertJsonPath('kpi.occupied', 0);

        // 2. Receptionist checks in a guest into room1
        $guest = Guest::create(['fname' => 'John', 'lname' => 'Doe', 'phone' => '09123456789']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => $now,
            'check_out_datetime' => $now->copy()->addHours(3),
            'booked_rooms' => [$this->room1->id],
            'hours' => 3,
            'stay_type' => 'short_stay',
            'status' => 'checked_in',
            'created_at' => $now,
        ]);
        $this->room1->update(['status' => 'occupied']);

        // 3. Admin Overview and Room endpoints immediately reflect new booking without refresh
        $resAfter = $this->getJson('/api/admin/overview-stats');
        $resAfter->assertOk()
            ->assertJson([
                'occupancy_today' => 1,
                'checkins_today' => 1,
            ]);

        $roomsAfter = $this->getJson('/api/dashboard/notifications');
        $roomsAfter->assertOk()
            ->assertJsonPath('kpi.available', 1)
            ->assertJsonPath('kpi.occupied', 1)
            ->assertJsonPath("room_statuses.{$this->room1->id}", 'occupied');
    }

    public function test_booking_stay_type_syncs_in_real_time_when_duration_edited_or_deleted(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $guest = Guest::create(['fname' => 'Jane', 'lname' => 'Smith', 'phone' => '09987654321']);

        // Create 3-hour booking
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09987654321',
            'check_in_datetime' => $now,
            'check_out_datetime' => $now->copy()->addHours(3),
            'booked_rooms' => [$this->room1->id],
            'hours' => 3,
            'stay_type' => 'short_stay',
            'status' => 'checked_in',
            'created_at' => $now,
        ]);

        $dateStr = $now->toDateString();
        $res1 = $this->getJson("/api/admin/stay-type-stats?date={$dateStr}");
        $res1->assertOk()
            ->assertJsonPath('counts.0', 1) // 3 Hours
            ->assertJsonPath('counts.3', 0); // 24 Hours

        // Receptionist edits booking duration from 3 Hours to 24 Hours
        $booking->update([
            'hours' => 24,
            'stay_type' => 'overnight',
            'check_out_datetime' => $now->copy()->addHours(24),
        ]);

        $res2 = $this->getJson("/api/admin/stay-type-stats?date={$dateStr}");
        $res2->assertOk()
            ->assertJsonPath('counts.0', 0) // 3 Hours becomes 0
            ->assertJsonPath('counts.3', 1); // 24 Hours becomes 1

        // Receptionist deletes the booking
        $booking->delete();

        $res3 = $this->getJson("/api/admin/stay-type-stats?date={$dateStr}");
        $res3->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('counts.0', 0)
            ->assertJsonPath('counts.3', 0);
    }

    public function test_revenue_and_sales_report_sync_in_real_time_when_payment_processed(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $today = $now->toDateString();

        $guest = Guest::create(['fname' => 'Carlos', 'lname' => 'Reyes', 'phone' => '09555555555']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09555555555',
            'check_in_datetime' => $now->copy()->subHours(6),
            'check_out_datetime' => $now,
            'booked_rooms' => [$this->room1->id],
            'hours' => 6,
            'stay_type' => 'short_stay',
            'status' => 'checked_in',
            'created_at' => $now->copy()->subHours(6),
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 1200.00,
            'extra_charges_total' => 0.00,
            'discounts_total' => 0.00,
            'grand_total' => 1200.00,
            'status' => 'unpaid',
            'created_at' => $now->copy()->subHours(6),
        ]);

        // Prior to payment: pending amount = 1200, revenue today = 0
        $overviewBefore = $this->getJson('/api/admin/overview-stats');
        $overviewBefore->assertOk()->assertJson(['revenue_today' => 0]);

        $salesBefore = $this->getJson("/api/admin/sales-report?date={$today}");
        $salesBefore->assertOk()
            ->assertJson([
                'total_revenue' => 0,
                'pending_amount' => 1200.00,
                'pending_guests_count' => 1,
            ]);

        // Receptionist processes payment
        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 1200.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now,
            'created_at' => $now,
        ]);
        $invoice->update(['status' => 'paid', 'updated_at' => $now]);
        $booking->update(['status' => 'checked_out', 'actual_check_out' => $now]);

        // After payment: Admin Overview & Sales Report reflect paid revenue and zero pending immediately
        $overviewAfter = $this->getJson('/api/admin/overview-stats');
        $overviewAfter->assertOk()
            ->assertJson([
                'revenue_today' => 1200.00,
                'checkouts_today' => 1,
            ]);

        $revStatsAfter = $this->getJson('/api/admin/revenue-stats');
        $revStatsAfter->assertOk()
            ->assertJsonStructure(['status', 'revenue_chart']);

        $salesAfter = $this->getJson("/api/admin/sales-report?date={$today}");
        $salesAfter->assertOk()
            ->assertJson([
                'total_revenue' => 1200.00,
                'pending_amount' => 0,
                'total_bookings' => 1,
            ]);
    }

    public function test_amenities_comparison_syncs_in_real_time_when_guest_purchases_amenities(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $today = $now->toDateString();

        $amenity = Amenity::create([
            'name' => 'Extra Slippers',
            'price' => 150.00,
            'charged' => 'per item',
            'offered' => true,
        ]);

        $guest = Guest::create(['fname' => 'Maria', 'lname' => 'Santos', 'phone' => '09333333333']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09333333333',
            'check_in_datetime' => $now,
            'check_out_datetime' => $now->copy()->addHours(12),
            'booked_rooms' => [$this->room1->id],
            'hours' => 12,
            'stay_type' => 'short_stay',
            'status' => 'checked_out',
            'created_at' => $now,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 1000.00,
            'extra_charges_total' => 300.00,
            'discounts_total' => 0.00,
            'grand_total' => 1300.00,
            'status' => 'paid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Receptionist logs amenity purchase
        AvailableAmenity::create([
            'booking_id' => $booking->id,
            'room_id' => $this->room1->id,
            'item_name' => 'Extra Slippers',
            'price' => 150.00,
            'quantity' => 2,
            'total_amount' => 300.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $salesReport = $this->getJson("/api/admin/sales-report?date={$today}");
        $salesReport->assertOk();

        $amenitiesItems = $salesReport->json('amenities_comparison.items');
        $this->assertNotEmpty($amenitiesItems);
        $slipperItem = collect($amenitiesItems)->firstWhere('name', 'Extra Slippers');
        $this->assertNotNull($slipperItem);
        $this->assertEquals(2, $slipperItem['count']);
        $this->assertEquals(300.00, $slipperItem['revenue']);
    }

    public function test_admin_scripts_contain_polling_intervals_for_all_data_dependent_components(): void
    {
        $scriptsContent = file_get_contents(resource_path('views/Administrator/partials/scripts.blade.php'));

        // 1. Overview cards polling
        $this->assertStringContainsString('setInterval(updateAdminCards, 5000)', $scriptsContent);

        // 2. Admin notifications polling
        $this->assertStringContainsString('setInterval(fetchAdminNotifications, 5000)', $scriptsContent);

        // 3. Stay type donut chart polling
        $this->assertStringContainsString('setInterval(fetchStayTypeData, 5000)', $scriptsContent);

        // 4. Sales report & amenities comparison polling
        $this->assertStringContainsString('setInterval(fetchSalesReport, 5000)', $scriptsContent);

        // 5. Room grid & KPI cards polling
        $this->assertStringContainsString('setInterval(syncAdminRooms, 5000)', $scriptsContent);
    }

    public function test_total_rooms_in_notifications_api_updates_when_room_added_or_deleted(): void
    {
        // setUp creates 2 rooms; verify initial kpi.total
        $res1 = $this->getJson('/api/dashboard/notifications');
        $res1->assertOk()->assertJsonPath('kpi.total', 2);

        // Add a third room
        Room::create([
            'room_number' => '103',
            'short_stay_rate' => 600,
            'overnight_rate' => 2200,
            'status' => 'available',
        ]);

        $res2 = $this->getJson('/api/dashboard/notifications');
        $res2->assertOk()->assertJsonPath('kpi.total', 3);

        // Remove the added room
        Room::where('room_number', '103')->delete();

        $res3 = $this->getJson('/api/dashboard/notifications');
        $res3->assertOk()->assertJsonPath('kpi.total', 2);
    }

    public function test_admin_scripts_update_total_rooms_label_from_kpi_total(): void
    {
        $scriptsContent = file_get_contents(resource_path('views/Administrator/partials/scripts.blade.php'));

        $this->assertStringContainsString('admin-total-rooms-label', $scriptsContent);
        $this->assertStringContainsString('data.kpi.total', $scriptsContent);
    }
}
