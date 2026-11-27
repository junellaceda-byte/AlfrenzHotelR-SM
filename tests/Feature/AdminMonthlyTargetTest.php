<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\MonthlyTarget;
use App\Models\PaymentLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonthlyTargetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_get_monthly_target_returns_default_when_not_set(): void
    {
        $response = $this->getJson('/api/admin/monthly-target?year=2026&month=5');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'year' => 2026,
                'month' => 5,
                'target_amount' => 150000.00,
                'target_formatted' => '₱150,000.00',
                'has_custom_target' => false,
                'progress_percent' => 0,
            ]);
    }

    public function test_admin_can_set_and_update_monthly_target_in_database(): void
    {
        // 1. Create initial target
        $createRes = $this->postJson('/api/admin/monthly-target', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 180000,
        ]);

        $createRes->assertOk()
            ->assertJson([
                'status' => 'success',
                'target_formatted' => '₱180,000.00',
            ]);

        $this->assertDatabaseHas('monthly_targets', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 180000.00,
        ]);

        // 2. Update existing target for same month/year (upsert)
        $updateRes = $this->postJson('/api/admin/monthly-target', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 250000.50,
        ]);

        $updateRes->assertOk()
            ->assertJson([
                'status' => 'success',
                'target_formatted' => '₱250,000.50',
            ]);

        $this->assertDatabaseCount('monthly_targets', 1);
        $this->assertDatabaseHas('monthly_targets', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 250000.50,
        ]);

        // 3. GET endpoint reflects saved custom target
        $getRes = $this->getJson('/api/admin/monthly-target?year=2026&month=11');
        $getRes->assertOk()
            ->assertJson([
                'has_custom_target' => true,
                'target_amount' => 250000.50,
                'target_formatted' => '₱250,000.50',
            ]);
    }

    public function test_save_monthly_target_validates_inputs(): void
    {
        $this->postJson('/api/admin/monthly-target', [
            'year' => 2026,
            'month' => 13,
            'target_amount' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['month', 'target_amount']);
    }

    public function test_sales_report_reflects_saved_target_and_recalculates_progress(): void
    {
        $now = Carbon::create(2026, 11, 15, 12, 0, 0, 'Asia/Manila');

        $guest = Guest::create([
            'fname' => 'Maria',
            'lname' => 'Santos',
            'contact_no' => '09171234567',
            'email' => 'maria@example.com',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [1],
            'stay_type' => 'overnight',
            'nights' => 1,
            'check_in_datetime' => $now->copy()->subHours(2),
            'check_out_datetime' => $now->copy()->addDay(),
            'status' => 'checked_out',
            'total_amount' => 50000.00,
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 50000.00,
            'extra_charges_total' => 0,
            'grand_total' => 50000.00,
            'status' => 'paid',
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 50000.00,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => $now,
        ]);

        // Set target to 200,000 -> 50,000 / 200,000 = 25%
        $this->postJson('/api/admin/monthly-target', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 200000,
        ])->assertOk();

        $report1 = $this->getJson('/api/admin/sales-report?year=2026&month=11');
        $report1->assertOk()
            ->assertJson([
                'monthly_target' => 200000.00,
                'monthly_target_formatted' => '₱200,000.00',
                'monthly_target_progress' => 25,
                'monthly_target_year' => 2026,
                'monthly_target_month' => 11,
                'has_custom_target' => true,
            ]);

        // Update target to 100,000 -> 50,000 / 100,000 = 50%
        $this->postJson('/api/admin/monthly-target', [
            'year' => 2026,
            'month' => 11,
            'target_amount' => 100000,
        ])->assertOk();

        $report2 = $this->getJson('/api/admin/sales-report?year=2026&month=11');
        $report2->assertOk()
            ->assertJson([
                'monthly_target' => 100000.00,
                'monthly_target_formatted' => '₱100,000.00',
                'monthly_target_progress' => 50,
            ]);
    }

    public function test_sales_report_custom_date_uses_target_for_that_dates_month(): void
    {
        MonthlyTarget::create([
            'year' => 2026,
            'month' => 12,
            'target_amount' => 30000.00,
        ]);

        // Even if month=11 is in the query string, custom date 2026-12-15 should resolve December's target
        $response = $this->getJson('/api/admin/sales-report?timeframe=custom&date=2026-12-15&year=2026&month=11');

        $response->assertOk()
            ->assertJson([
                'monthly_target' => 30000.00,
                'monthly_target_formatted' => '₱30,000.00',
                'monthly_target_year' => 2026,
                'monthly_target_month' => 12,
                'has_custom_target' => true,
            ]);
    }

    public function test_sales_report_view_renders_saved_current_month_target(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        MonthlyTarget::create([
            'year' => (int) $now->format('Y'),
            'month' => (int) $now->format('n'),
            'target_amount' => 275000.00,
        ]);

        $response = $this->get('/admindashboard?section=salerep');

        $response->assertOk();
        $response->assertSee('₱275,000.00', false);
        $response->assertSee('Achieved of ₱275,000.00 Goal', false);
        $response->assertSee('id="setTargetCurrentHint"', false);
    }
}
