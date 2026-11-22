<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistBookingExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_booking_history_displays_search_bar_and_export_to_excel_button(): void
    {
        $response = $this->get('/bookings');

        $response->assertOk();
        $response->assertSee('id="bookingSearchInput"', false);
        $response->assertSee('Search bookings...', false);
        $response->assertSee('id="openExportBookingModalBtn"', false);
        $response->assertSee('Export to Excel');
    }

    public function test_export_modal_contains_presets_calendar_fields_and_modal_export_button(): void
    {
        $response = $this->get('/bookings');

        $response->assertOk();
        $response->assertSee('id="exportBookingModal"', false);

        // 1. Date Range Presets: Last 7 Days, This Month, Last Month, and Custom
        $response->assertSee('Last 7 Days');
        $response->assertSee('This Month');
        $response->assertSee('Last Month');
        $response->assertSee('Custom');

        // 2. Calendar Date Filters: From and To, M/D/Y format
        $response->assertSee('From');
        $response->assertSee('To');
        $response->assertSee('M/D/Y');
        $response->assertSee('id="bookingExportFrom"', false);
        $response->assertSee('id="bookingExportTo"', false);

        // 3. Export button at the bottom of the modal
        $response->assertSee('id="confirmExportBookingBtn"', false);
        $response->assertSee('Export to Excel');
    }

    public function test_export_route_returns_csv_download_response(): void
    {
        $response = $this->get('/bookings/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Booking_History_', $response->headers->get('Content-Disposition'));
    }

    public function test_export_with_preset_filters_generates_correct_period_labels(): void
    {
        // Preset: last7
        $resLast7 = $this->get('/bookings/export?preset=last7');
        $resLast7->assertOk();
        $this->assertStringContainsString('Last_7_Days', $resLast7->headers->get('Content-Disposition'));

        // Preset: thisMonth
        $resThisMonth = $this->get('/bookings/export?preset=thisMonth');
        $resThisMonth->assertOk();
        $this->assertStringContainsString('This_Month_', $resThisMonth->headers->get('Content-Disposition'));

        // Preset: lastMonth
        $resLastMonth = $this->get('/bookings/export?preset=lastMonth');
        $resLastMonth->assertOk();
        $this->assertStringContainsString('Last_Month_', $resLastMonth->headers->get('Content-Disposition'));
    }

    public function test_export_with_custom_date_range_includes_matching_booking_records(): void
    {
        $room = Room::create([
            'room_number' => '201',
            'short_stay_rate' => 800.00,
            'overnight_rate' => 2500.00,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'fname' => 'Leonard',
            'lname' => 'Hofstadter',
            'phone' => '09181112222',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09181112222',
            'booked_rooms' => [$room->id],
            'nights' => 2,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_out',
            'check_in_datetime' => '2026-11-10 14:00:00',
            'check_out_datetime' => '2026-11-12 12:00:00',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-BK-EXP-001',
            'room_charges' => 5000.00,
            'extra_charges_total' => 0.00,
            'grand_total' => 5000.00,
            'status' => 'paid',
            'updated_at' => Carbon::parse('2026-11-12 12:00:00'),
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 5000.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => Carbon::parse('2026-11-12 12:00:00'),
        ]);

        // Request export covering November 09 to November 15, 2026
        $response = $this->get('/bookings/export?from=2026-11-09&to=2026-11-15');
        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('HOTEL BOOKING HISTORY REPORT', $content);
        $this->assertStringContainsString('SUMMARY METRICS', $content);
        $this->assertStringContainsString('Leonard', $content);
        $this->assertStringContainsString('Hofstadter', $content);
        $this->assertStringContainsString('BK-'.str_pad($booking->id, 4, '0', STR_PAD_LEFT), $content);
        $this->assertStringContainsString('5,000.00', $content);
    }

    public function test_export_excludes_records_outside_date_range(): void
    {
        $guest = Guest::create([
            'fname' => 'Sheldon',
            'lname' => 'Cooper',
            'phone' => '09183334444',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09183334444',
            'booked_rooms' => [1],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_out',
            'check_in_datetime' => '2026-05-01 10:00:00',
            'check_out_datetime' => '2026-05-02 12:00:00',
        ]);
        $booking->created_at = Carbon::parse('2026-05-01 10:00:00');
        $booking->updated_at = Carbon::parse('2026-05-02 12:00:00');
        $booking->save(['timestamps' => false]);

        // Query for November 2026
        $response = $this->get('/bookings/export?from=2026-11-01&to=2026-11-30');
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringNotContainsString('Sheldon', $content);
        $this->assertStringNotContainsString('Cooper', $content);
    }

    public function test_booking_search_filters_records_by_guest_name(): void
    {
        $guest1 = Guest::create(['fname' => 'Penny', 'lname' => 'Teller', 'phone' => '09187778888']);
        Booking::create([
            'guest_id' => $guest1->id,
            'phone' => '09187778888',
            'booked_rooms' => [5],
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-21 14:00:00',
            'check_out_datetime' => '2026-11-22 12:00:00',
        ]);

        $guest2 = Guest::create(['fname' => 'Howard', 'lname' => 'Wolowitz', 'phone' => '09189990000']);
        Booking::create([
            'guest_id' => $guest2->id,
            'phone' => '09189990000',
            'booked_rooms' => [6],
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-21 14:00:00',
            'check_out_datetime' => '2026-11-22 12:00:00',
        ]);

        $response = $this->get('/bookings?search=Penny');
        $response->assertOk();
        $response->assertSee('Penny');
        $response->assertDontSee('Wolowitz');
    }
}
