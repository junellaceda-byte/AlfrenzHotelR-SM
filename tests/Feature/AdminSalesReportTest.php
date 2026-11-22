<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Room $room2;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        // Establish rooms
        $this->room1 = Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $this->room2 = Room::create([
            'room_number' => '02',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'contact_no' => '09123456789',
            'email' => 'juan@example.com',
        ]);
    }

    public function test_sales_report_api_returns_success_and_valid_structure(): void
    {
        $response = $this->getJson('/api/admin/sales-report');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'period_title',
            'revenue_card_title',
            'selected_year',
            'selected_month',
            'total_revenue',
            'total_revenue_formatted',
            'adr',
            'adr_formatted',
            'total_bookings',
            'monthly_target',
            'monthly_target_formatted',
            'monthly_target_progress',
            'lost_revenue',
            'lost_revenue_formatted',
            'cancellations_count',
            'pending_amount',
            'pending_amount_formatted',
            'pending_guests_count',
            'occupancy_trend' => [
                'weekly' => ['labels', 'data', 'revenue'],
                'monthly' => ['labels', 'data', 'revenue'],
                'yearly' => ['labels', 'data', 'revenue'],
            ],
            'revenue_breakdown' => [
                'weekly' => ['labels', 'roomRate', 'extraCharges'],
                'monthly' => ['labels', 'roomRate', 'extraCharges'],
            ],
            'amenities_comparison' => [
                'subtitle',
                'items',
            ],
        ]);
    }

    public function test_sales_report_aggregates_receptionist_billing_confirmed_payments(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        // Create confirmed booking and paid invoice
        $booking = Booking::create([
            'guest_id' => $this->guest->id,
            'booked_rooms' => [$this->room1->id],
            'stay_type' => 'overnight',
            'nights' => 2,
            'status' => 'checked_out',
            'check_in_datetime' => $now->copy()->subDays(2),
            'check_out_datetime' => $now,
            'actual_check_out' => $now,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 4400.00,
            'extra_charges_total' => 500.00,
            'discount_amount' => 0.00,
            'grand_total' => 4900.00,
            'status' => 'paid',
            'updated_at' => $now,
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 4900.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now,
            'created_at' => $now,
        ]);

        // Add an amenity to the invoice
        AvailableAmenity::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'item_name' => 'Extra Bed',
            'quantity' => 1,
            'unit_price' => 500.00,
            'total_amount' => 500.00,
            'is_available' => true,
        ]);

        // Query sales report for this month
        $response = $this->getJson("/api/admin/sales-report?year={$year}&month={$month}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(4900.00, $data['total_revenue']);
        $this->assertEquals(1, $data['total_bookings']);
        $this->assertEquals(2200.00, $data['adr']); // 4400 / 2 room nights = 2200

        // Verify Amenities Comparison reflects the added amenity
        $this->assertNotEmpty($data['amenities_comparison']['items']);
        $extraBed = collect($data['amenities_comparison']['items'])->firstWhere('name', 'Extra Bed');
        $this->assertNotNull($extraBed);
        $this->assertEquals(1, $extraBed['count']);
        $this->assertEquals(500.00, $extraBed['revenue']);
    }

    public function test_sales_report_reflects_pending_payments_and_lost_revenue_from_cancellations(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        // 1. Pending invoice from Receptionist Billing
        $pendingBooking = Booking::create([
            'guest_id' => $this->guest->id,
            'booked_rooms' => [$this->room2->id],
            'stay_type' => 'overnight',
            'status' => 'checked_in',
            'check_in_datetime' => $now,
            'check_out_datetime' => $now->copy()->addDay(),
        ]);

        Invoice::create([
            'booking_id' => $pendingBooking->id,
            'room_charges' => 2200.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'grand_total' => 2200.00,
            'status' => 'unpaid',
            'due_date' => $now->toDateString(),
        ]);

        // 2. Cancelled reservation
        Reservation::create([
            'guest_name' => 'Maria Santos',
            'phone_number' => '09991234567',
            'reservation_start_date' => $now->copy()->addDays(2),
            'reservation_end_date' => $now->copy()->addDays(4),
            'number_of_rooms' => 1,
            'selected_rooms' => [$this->room1->id],
            'status' => 'cancelled',
            'deposit' => 1000.00,
            'updated_at' => $now,
        ]);

        $response = $this->getJson("/api/admin/sales-report?year={$year}&month={$month}");

        $response->assertStatus(200);
        $data = $response->json();

        // Pending payments check
        $this->assertEquals(1, $data['pending_guests_count']);
        $this->assertEquals(2200.00, $data['pending_amount']);

        // Cancellations check
        $this->assertEquals(1, $data['cancellations_count']);
        $this->assertEquals(1000.00, $data['lost_revenue']);
    }

    public function test_can_save_monthly_target_and_updates_progress(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $response = $this->postJson('/api/admin/monthly-target', [
            'year' => $year,
            'month' => $month,
            'target_amount' => 200000.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'target' => [
                'year' => $year,
                'month' => $month,
                'target_amount' => '200000.00',
            ],
            'target_formatted' => '₱200,000.00',
        ]);

        $this->assertDatabaseHas('monthly_targets', [
            'year' => $year,
            'month' => $month,
            'target_amount' => 200000.00,
        ]);

        // Fetch sales report and verify target is 200,000
        $reportResponse = $this->getJson("/api/admin/sales-report?year={$year}&month={$month}");
        $reportResponse->assertStatus(200);
        $this->assertEquals(200000.00, $reportResponse->json('monthly_target'));
    }

    public function test_sales_report_specific_date_filter(): void
    {
        $specificDate = '2026-11-19';

        $response = $this->getJson("/api/admin/sales-report?date={$specificDate}");

        $response->assertStatus(200);
        $this->assertStringContainsString('Nov 19, 2026', $response->json('period_title'));
    }
}
