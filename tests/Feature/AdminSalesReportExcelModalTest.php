<?php

namespace Tests\Feature;

use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AdminSalesReportExcelModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_sales_reports_page_contains_new_filters_and_export_modal(): void
    {
        $response = $this->get('/admindashboard?section=salerep');

        $response->assertOk();

        // 1. Filter controls retain This Month and Today, and include Yearly, Monthly, Weekly, Custom
        // Today is active by default
        $response->assertSee('id="salesFilterTodayBtn" class="btn btn-sm sales-filter-btn active"', false);
        $response->assertSee('Today');
        $response->assertSee('id="salesFilterThisMonthBtn"', false);
        $response->assertSee('This Month');
        $response->assertSee('id="salesFilterYearlyBtn"', false);
        $response->assertSee('Yearly');
        $response->assertSee('id="salesFilterMonthlyBtn"', false);
        $response->assertSee('Monthly');
        $response->assertSee('id="salesFilterWeeklyBtn"', false);
        $response->assertSee('Weekly');
        $response->assertSee('id="salesFilterCustomBtn"', false);
        $response->assertSee('Custom');

        // Initial scope badge and subtitle default to Today
        $response->assertSee('id="occupancyTrendScopeBadge" class="badge text-bg-light border text-secondary fw-semibold">', false);
        $response->assertSee('Today');
        $response->assertSee('Showing live sales data for Today');

        // 2. Custom date picker uses M/D/Y placeholder
        $response->assertSee('id="salesCustomDateInput"', false);
        $response->assertSee('placeholder="M/D/Y"', false);

        // 3. Occupancy chart separate timeframe buttons are removed
        $response->assertDontSee('occupancy-trend-btn');

        // 4. Export to Excel button links to the customization modal
        $response->assertSee('id="salesExportExcelBtn"', false);
        $response->assertSee('data-bs-target="#salesExportExcelModal"', false);

        // 5. Excel customization modal elements
        $response->assertSee('id="salesExportExcelModal"', false);
        $response->assertSee('Export to Excel Customization');
        $response->assertSee('id="excelHeaderBg"', false);
        $response->assertSee('id="excelHeaderText"', false);
        $response->assertSee('id="excelBodyText"', false);
        $response->assertSee('id="excelAccentColor"', false);
        $response->assertSee('id="excelRowBg"', false);
        $response->assertSee('id="excelSummaryColor"', false);
        $response->assertSee('id="excelPreviewTable"', false);
        $response->assertSee('Download Excel (.xlsx)');
    }

    public function test_sales_report_api_supports_all_timeframes_and_connects_occupancy_chart(): void
    {
        // 1. This Month
        $resMonth = $this->getJson('/api/admin/sales-report?timeframe=this_month');
        $resMonth->assertOk();
        $this->assertNotEmpty($resMonth->json('occupancy_trend.labels'));

        // 2. Today
        $resToday = $this->getJson('/api/admin/sales-report?timeframe=today');
        $resToday->assertOk();
        $todayLabels = $resToday->json('occupancy_trend.labels');
        $this->assertContains('12 AM - 4 AM', $todayLabels);
        $this->assertContains('12 PM - 4 PM', $todayLabels);

        // 3. Weekly
        $resWeekly = $this->getJson('/api/admin/sales-report?timeframe=weekly');
        $resWeekly->assertOk();
        $weekLabels = $resWeekly->json('occupancy_trend.labels');
        $this->assertContains('Mon', $weekLabels);
        $this->assertContains('Sun', $weekLabels);

        // 4. Monthly
        $resMonthly = $this->getJson('/api/admin/sales-report?timeframe=monthly');
        $resMonthly->assertOk();
        $monthlyLabels = $resMonthly->json('occupancy_trend.labels');
        $this->assertContains('January', $monthlyLabels);
        $this->assertContains('December', $monthlyLabels);

        // 5. Yearly
        $resYearly = $this->getJson('/api/admin/sales-report?timeframe=yearly');
        $resYearly->assertOk();
        $this->assertCount(5, $resYearly->json('occupancy_trend.labels'));

        // 6. Custom single date (exact date filter in M/D/Y format)
        $resCustom = $this->getJson('/api/admin/sales-report?timeframe=custom&date=11/19/2026');
        $resCustom->assertOk();
        $this->assertStringContainsString('Nov 19, 2026', $resCustom->json('period_title'));
        $this->assertContains('12 AM - 4 AM', $resCustom->json('occupancy_trend.labels'));
    }

    public function test_export_route_generates_valid_custom_styled_xlsx_spreadsheet(): void
    {
        $response = $this->get('/admin/sales-report/export?format=xlsx&timeframe=this_month&header_bg=%231E3A8A&summary_color=%23059669');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        // Save content to temporary file to inspect OpenXML zip structure
        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tempPath, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempPath));

        // Check required OpenXML files
        $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));
        $this->assertNotFalse($zip->getFromName('xl/workbook.xml'));
        $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
        $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet1.xml'));
        $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet2.xml'));
        $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet3.xml'));

        // Verify custom colors are injected into xl/styles.xml
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $this->assertStringContainsString('1E3A8A', $stylesXml);
        $this->assertStringContainsString('059669', $stylesXml);

        $zip->close();
        @unlink($tempPath);
    }

    public function test_export_route_xlsx_contains_complete_filtered_dataset(): void
    {
        $room = Room::create([
            'room_number' => '10',
            'short_stay_rate' => 900.00,
            'overnight_rate' => 2800.00,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'fname' => 'Michael',
            'lname' => 'Corleone',
            'phone' => '09181112233',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09181112233',
            'booked_rooms' => [$room->id],
            'nights' => 3,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_out',
            'check_in_datetime' => '2026-11-15 14:00:00',
            'check_out_datetime' => '2026-11-18 12:00:00',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 8400.00,
            'extra_charges_total' => 600.00,
            'grand_total' => 9000.00,
            'status' => 'paid',
            'updated_at' => '2026-11-18 12:30:00',
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 9000.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => '2026-11-18 12:30:00',
        ]);

        AvailableAmenity::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'item_name' => 'Extra Soft Towels',
            'price' => 300.00,
            'quantity' => 2,
            'total_amount' => 600.00,
        ]);

        // Request export for November 2026
        $response = $this->get('/admin/sales-report/export?format=xlsx&year=2026&month=11');
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_full_');
        file_put_contents($tempPath, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempPath));

        // Sheet 1: Financial performance & amenities
        $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('KEY FINANCIAL PERFORMANCE METRICS', $sheet1);
        $this->assertStringContainsString('REVENUE BREAKDOWN', $sheet1);
        $this->assertStringContainsString('OCCUPANCY &amp; REVENUE TREND DATA', $sheet1);
        $this->assertStringContainsString('AMENITIES COMPARISON', $sheet1);
        $this->assertStringContainsString('Extra Soft Towels', $sheet1);

        // Sheet 2: Confirmed payments and guest data
        $sheet2 = $zip->getFromName('xl/worksheets/sheet2.xml');
        $this->assertStringContainsString('CONFIRMED PAYMENTS', $sheet2);
        $this->assertStringContainsString('Michael Corleone', $sheet2);
        $this->assertStringContainsString('8400', $sheet2);

        $zip->close();
        @unlink($tempPath);
    }

    public function test_sales_reports_defaults_to_today_when_opened_or_reloaded_and_returning_from_other_sections(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $todayFormatted = $now->format('M j, Y');

        // 1. Initial access to Sales Reports
        $response1 = $this->get('/admindashboard?section=salerep');
        $response1->assertOk();
        $response1->assertSee('id="salesFilterTodayBtn" class="btn btn-sm sales-filter-btn active"', false);
        $response1->assertSee('Showing live sales data for Today');

        // 2. Navigate away to Overview
        $responseOverview = $this->get('/admindashboard?section=overview');
        $responseOverview->assertOk();

        // 3. Navigate back to Sales Reports
        $response2 = $this->get('/admindashboard?section=salerep');
        $response2->assertOk();
        $response2->assertSee('id="salesFilterTodayBtn" class="btn btn-sm sales-filter-btn active"', false);
        $response2->assertSee('Showing live sales data for Today');

        // 4. API default response without parameters reflects Today
        $apiResponse = $this->getJson('/api/admin/sales-report');
        $apiResponse->assertOk();
        $this->assertEquals($todayFormatted, $apiResponse->json('period_title'));
        $this->assertEquals('Total Revenue (Today)', $apiResponse->json('revenue_card_title'));
        $this->assertEquals('today', $apiResponse->json('occupancy_trend.active_timeframe'));
        $this->assertContains('12 AM - 4 AM', $apiResponse->json('occupancy_trend.labels'));
        $this->assertContains('12 PM - 4 PM', $apiResponse->json('occupancy_trend.labels'));
    }
}
