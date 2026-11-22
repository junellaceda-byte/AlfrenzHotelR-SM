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

class AdminSalesReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_export_route_returns_csv_download_response(): void
    {
        $response = $this->get('/admin/sales-report/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_export_filename_includes_month_and_year_for_month_filter(): void
    {
        $response = $this->get('/admin/sales-report/export?year=2026&month=11');

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('November_2026', $disposition);
    }

    public function test_export_filename_includes_date_for_specific_day_filter(): void
    {
        $response = $this->get('/admin/sales-report/export?date=2026-11-15');

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Nov_15_2026', $disposition);
    }

    public function test_export_filename_includes_year_for_all_months_filter(): void
    {
        $response = $this->get('/admin/sales-report/export?year=2026&month=all');

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Year_2026', $disposition);
    }

    public function test_export_csv_contains_summary_section_headers(): void
    {
        $response = $this->get('/admin/sales-report/export?year=2026&month=11');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('SALES REPORT SUMMARY', $content);
        $this->assertStringContainsString('Total Revenue', $content);
        $this->assertStringContainsString('Average Daily Rate', $content);
        $this->assertStringContainsString('Total Bookings', $content);
        $this->assertStringContainsString('Monthly Target', $content);
        $this->assertStringContainsString('Lost Revenue', $content);
        $this->assertStringContainsString('Pending Payments', $content);
    }

    public function test_export_csv_contains_paid_invoice_detail_rows(): void
    {
        $room = Room::create([
            'room_number' => '05',
            'short_stay_rate' => 800.00,
            'overnight_rate' => 2500.00,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'fname' => 'Jane',
            'lname' => 'Doe',
            'phone' => '09171112222',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09171112222',
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
            'invoice_number' => 'INV-TEST-001',
            'room_charges' => 4400.00,
            'extra_charges_total' => 500.00,
            'grand_total' => 4900.00,
            'status' => 'paid',
            'updated_at' => Carbon::parse('2026-11-12 12:00:00'),
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 4900.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => Carbon::parse('2026-11-12 12:00:00'),
        ]);

        $response = $this->get('/admin/sales-report/export?year=2026&month=11');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('PAID INVOICES DETAIL', $content);
        $this->assertStringContainsString('Jane Doe', $content);
        $this->assertStringContainsString('4,900.00', $content);
    }

    public function test_export_csv_today_filter_returns_valid_response(): void
    {
        $today = now()->setTimezone('Asia/Manila')->toDateString();

        $response = $this->get('/admin/sales-report/export?date='.$today);

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('SALES REPORT SUMMARY', $content);
    }
}
