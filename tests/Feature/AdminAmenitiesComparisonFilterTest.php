<?php

namespace Tests\Feature;

use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAmenitiesComparisonFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed an admin session
        $this->withSession([
            'role' => 'admin',
            'admin_id' => 1,
            'staff_id' => 1,
        ]);
    }

    public function test_sales_report_page_contains_weekly_monthly_and_yearly_filter_buttons(): void
    {
        $response = $this->get('/admindashboard?section=salerep');

        $response->assertStatus(200);
        $response->assertSee('Amenities Comparison');
        $response->assertSee('data-period="weekly"', false);
        $response->assertSee('data-period="monthly"', false);
        $response->assertSee('data-period="yearly"', false);

        // Main filter buttons and sliding indicator element
        $response->assertSee('id="salesFilterGroup"', false);
        $response->assertSee('id="salesFilterSlider"', false);
        $response->assertSee('data-filter="this_month"', false);
        $response->assertSee('data-filter="today"', false);
        $response->assertSee('data-filter="yearly"', false);
        $response->assertSee('data-filter="monthly"', false);
        $response->assertSee('data-filter="weekly"', false);
        $response->assertSee('data-filter="custom"', false);
    }

    public function test_independent_amenities_comparison_endpoint_returns_success_and_correct_periods(): void
    {
        $guest = Guest::create([
            'fname' => 'John',
            'lname' => 'Doe',
            'email' => 'john@example.com',
            'contactno' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'nightly',
            'status' => 'confirmed',
            'check_in_datetime' => now()->startOfDay(),
            'check_out_datetime' => now()->addDays(2)->endOfDay(),
            'nights' => 2,
            'booked_rooms' => json_encode(['101']),
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'room_charges' => 2000,
            'extra_charges_total' => 300,
            'grand_total' => 2300,
            'status' => 'paid',
            'updated_at' => now(),
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2300,
            'payment_method' => 'cash',
            'status' => 'successful',
            'paid_at' => now(),
        ]);

        AvailableAmenity::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'item_name' => 'Extra Pillow',
            'quantity' => 3,
            'unit_price' => 100,
            'total_amount' => 300,
        ]);

        // 1. Weekly test
        $responseWeekly = $this->getJson('/api/admin/sales-report/amenities-comparison?period=weekly');
        $responseWeekly->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'period' => 'weekly',
            ])
            ->assertJsonPath('items.0.name', 'Extra Pillow')
            ->assertJsonPath('items.0.count', 3);

        // 2. Monthly test
        $responseMonthly = $this->getJson('/api/admin/sales-report/amenities-comparison?period=monthly');
        $responseMonthly->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'period' => 'monthly',
            ])
            ->assertJsonPath('items.0.name', 'Extra Pillow');

        // 3. Yearly test
        $responseYearly = $this->getJson('/api/admin/sales-report/amenities-comparison?period=yearly');
        $responseYearly->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'period' => 'yearly',
            ])
            ->assertJsonPath('items.0.name', 'Extra Pillow');
    }

    public function test_main_sales_report_api_includes_precomputed_amenities_periods(): void
    {
        $response = $this->getJson('/api/admin/sales-report?timeframe=today');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'period_title',
            'total_revenue',
            'amenities_comparison' => [
                'subtitle',
                'items',
                'weekly',
                'monthly',
                'yearly',
            ],
        ]);
    }
}
