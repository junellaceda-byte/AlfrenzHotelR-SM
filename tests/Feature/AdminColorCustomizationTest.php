<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminColorCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        SystemSetting::set('date_format', 'M d, Y');
        SystemSetting::set('time_format', 'h:i A');
    }

    /**
     * Test Global Body Background Beige #F5F7DF in Administrator Layout.
     */
    public function test_admin_dashboard_has_beige_body_and_content_background(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        // Body and main have background-color: #F5F7DF
        $response->assertSee('style="background-color: #F5F7DF;', false);
    }

    /**
     * Test Overview Check-ins Today and Check-outs Today cards use Blue.
     */
    public function test_overview_checkins_and_checkouts_cards_are_blue(): void
    {
        $response = $this->get('/admindashboard?section=overview');
        $response->assertOk();

        // Cards have Blue styling
        $response->assertSee('highlight-blue-3d', false);
        $response->assertSee('Check-ins Today');
        $response->assertSee('Check-outs Today');
    }

    /**
     * Test Overview Booking Stay Type and Revenue Statistics containers have Beige #F5F7DF.
     */
    public function test_overview_booking_stay_type_and_revenue_statistics_containers_are_beige(): void
    {
        $response = $this->get('/admindashboard?section=overview');
        $response->assertOk();

        // Revenue Statistics container has Beige #F5F7DF
        $response->assertSee('Revenue Statistics');
        // Booking Stay Type container has Beige #F5F7DF
        $response->assertSee('Booking Stay Type');
        $response->assertSee('style="background-color: #F5F7DF;"', false);
    }

    /**
     * Test Overview Notifications and Modal have Vintage Brown Header/Footer and Beige Body.
     */
    public function test_notifications_have_vintage_brown_header_footer_and_beige_body(): void
    {
        $response = $this->get('/admindashboard?section=overview');
        $response->assertOk();

        // Header: #443627
        $response->assertSee('background-color: #443627', false);
        // Body: #F5F7DF
        $response->assertSee('background-color: #F5F7DF', false);
        $response->assertSee('NOTIFICATIONS');
        $response->assertSee('adminNotificationsModal');
    }

    /**
     * Test Sales Reports Filter container has Vintage Brown #443627, and charts have Beige #F5F7DF.
     */
    public function test_sales_reports_containers_colors(): void
    {
        $response = $this->get('/admindashboard?section=salerep');
        $response->assertOk();

        // 1. Sales Reports Filter Container: Vintage Brown #443627
        $response->assertSee('sales-reports-filter-bar', false);
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('Sales Reports Filter');
        $response->assertSee('#F5F7DF', false);

        // 2. Occupancy & Revenue Trend Container: Beige #F5F7DF
        $response->assertSee('Occupancy & Revenue Trend', false);

        // 3. Monthly Target Progress Container: Beige #F5F7DF
        $response->assertSee('Monthly Target Progress');

        // 4. Revenue Breakdown: Beige #F5F7DF
        $response->assertSee('Revenue Breakdown: Room Rates vs Extra Charges');

        // 5. Amenities Comparison: outer container #EFEABB
        $response->assertSee('Amenities Comparison');
        $response->assertSee('background-color: #EFEABB', false);
    }

    /**
     * Test Amenities Section has Beige #F5F7DF container, data rows, and Vintage Brown #443627 table header.
     */
    public function test_amenities_section_container_and_table_header_colors(): void
    {
        Amenity::create([
            'name' => 'Extra Pillow',
            'price' => 150.00,
            'charged' => 'piece',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();

        // Amenities container: #F5F7DF
        $response->assertSee('background-color: #F5F7DF', false);

        // Table Header: #443627
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('Amenity');
        $response->assertSee('Price');
        $response->assertSee('Description');
        $response->assertSee('Status');
        $response->assertSee('Actions');

        // Data rows have Beige #F5F7DF background
        $response->assertSee('amenity-row', false);
        $response->assertSee('style="background-color: #F5F7DF;"', false);
    }

    /**
     * Test Administrator Topbar has #AEAC78 color and proper contrast.
     */
    public function test_admin_topbar_has_aeac78_color_and_proper_contrast(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        // Topbar header container: #AEAC78
        $response->assertSee('background-color: #AEAC78 !important;', false);

        // Greeting has #443627 for high contrast against #AEAC78
        $response->assertSee('color: #443627 !important;', false);

        // Date & Time boxes have #443627 background and #F5F7DF text
        $response->assertSee('background-color: #443627; border: 1px solid #443627; color: #F5F7DF;', false);

        // Admin avatar has #443627 background and #F5F7DF text
        $response->assertSee('title="Administrator"', false);
    }

    /**
     * Test Settings section matches Receptionist settings styling with Vintage Brown #443627.
     */
    public function test_admin_settings_matches_receptionist_styling(): void
    {
        $response = $this->get('/admindashboard?section=settings');
        $response->assertOk();

        // Outer Card: Vintage Brown #443627
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('System Settings');
        $response->assertSee('Administrator Portal');
        $response->assertSee('Live Manila Timestamp Preview');
    }

    /**
     * Test Logout Confirmation Modal has dedicated header with Vintage Brown #443627, Beige Body, Vintage Brown Footer.
     */
    public function test_logout_modal_has_dedicated_vintage_brown_header_and_footer(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        $response->assertSee('id="logoutConfirmModal"', false);
        $response->assertSee('Confirm Logout');
        $response->assertSee('background-color: #443627', false);
        $response->assertSee('background-color: #F5F7DF', false);
    }
}
