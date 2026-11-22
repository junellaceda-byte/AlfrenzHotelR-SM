<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingStayTypeChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_overview_page_renders_booking_stay_type_chart_and_timeframe_controls(): void
    {
        $response = $this->get('/admindashboard?section=overview');

        $response->assertOk();
        $response->assertSee('Booking Stay Type');
        $response->assertSee('id="bookingSourceChart"', false);

        // Three independent timeframe options: Weekly, Monthly, Yearly
        $response->assertSee('data-tf="weekly"', false);
        $response->assertSee('data-tf="monthly"', false);
        $response->assertSee('data-tf="yearly"', false);
        $response->assertSee('Weekly');
        $response->assertSee('Monthly');
        $response->assertSee('Yearly');

        // Calendar filter controls
        $response->assertSee('id="stayTypeDatePicker"', false);
        $response->assertSee('id="stayTypeMonthSelect"', false);
        $response->assertSee('id="stayTypeWeekSelect"', false);
        $response->assertSee('id="stayTypeYearSelect"', false);
        $response->assertSee('id="stayTypeLegendRows"', false);
    }

    public function test_stay_type_stats_categorizes_actual_booking_records_into_durations_and_percentages(): void
    {
        $guest = Guest::create([
            'fname' => 'Alice',
            'lname' => 'Smith',
            'phone' => '09171112233',
        ]);

        // 1. 3 Hours (Short Stay)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'booked_rooms' => [1],
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-10 10:00:00',
            'check_out_datetime' => '2026-11-10 13:00:00',
        ]);

        // 2. 6 Hours (Short Stay)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'booked_rooms' => [2],
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-11 10:00:00',
            'check_out_datetime' => '2026-11-11 16:00:00',
        ]);

        // 3. 12 Hours (Short Stay)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 12,
            'booked_rooms' => [3],
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-12 08:00:00',
            'check_out_datetime' => '2026-11-12 20:00:00',
        ]);

        // 4. 24 Hours (Long Stay) - 2 bookings
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'nights' => 1,
            'booked_rooms' => [4],
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-13 14:00:00',
            'check_out_datetime' => '2026-11-14 12:00:00',
        ]);

        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'nights' => 1,
            'booked_rooms' => [5],
            'status' => 'checked_out',
            'check_in_datetime' => '2026-11-14 14:00:00',
            'check_out_datetime' => '2026-11-15 12:00:00',
        ]);

        // Total: 5 bookings:
        // 3 Hours: 1 (20.0%)
        // 6 Hours: 1 (20.0%)
        // 12 Hours: 1 (20.0%)
        // 24 Hours: 2 (40.0%)

        $response = $this->getJson('/api/admin/stay-type-stats?timeframe=monthly&year=2026&month=11');

        $response->assertOk();
        $response->assertJson([
            'timeframe' => 'monthly',
            'year' => 2026,
            'month' => 11,
            'total' => 5,
            'labels' => ['3 Hours', '6 Hours', '12 Hours', '24 Hours'],
            'counts' => [1, 1, 1, 2],
            'percentages' => [20.0, 20.0, 20.0, 40.0],
        ]);

        // Verify distinct colors are returned
        $colors = $response->json('colors');
        $this->assertCount(4, $colors);
        $this->assertCount(4, array_unique($colors));

        // Verify categories detail structure
        $categories = $response->json('categories');
        $this->assertEquals('Short Stays', $categories[0]['group']);
        $this->assertEquals('3 Hours', $categories[0]['label']);
        $this->assertEquals(1, $categories[0]['count']);
        $this->assertEquals(20.0, $categories[0]['percentage']);

        $this->assertEquals('Long Stays', $categories[3]['group']);
        $this->assertEquals('24 Hours', $categories[3]['label']);
        $this->assertEquals(2, $categories[3]['count']);
        $this->assertEquals(40.0, $categories[3]['percentage']);
    }

    public function test_stay_type_stats_monthly_timeframe_groups_by_12_calendar_months(): void
    {
        $guest = Guest::create(['fname' => 'Bob', 'lname' => 'Taylor', 'phone' => '09172223344']);

        // Booking in January 2026
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'booked_rooms' => [1],
            'check_in_datetime' => '2026-01-15 10:00:00',
            'check_out_datetime' => '2026-01-15 13:00:00',
        ]);

        // Booking in July 2026
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'booked_rooms' => [2],
            'check_in_datetime' => '2026-07-20 14:00:00',
            'check_out_datetime' => '2026-07-21 12:00:00',
        ]);

        $response = $this->getJson('/api/admin/stay-type-stats?timeframe=monthly&year=2026&month=all');

        $response->assertOk();
        $grouped = $response->json('grouped_data');

        // Must be grouped by the 12 calendar months from January through December
        $this->assertCount(12, $grouped);
        $this->assertEquals('January', $grouped[0]['label']);
        $this->assertEquals(1, $grouped[0]['total']);
        $this->assertEquals(1, $grouped[0]['counts']['3_hours']);

        $this->assertEquals('July', $grouped[6]['label']);
        $this->assertEquals(1, $grouped[6]['total']);
        $this->assertEquals(1, $grouped[6]['counts']['24_hours']);

        $this->assertEquals('December', $grouped[11]['label']);
        $this->assertEquals(0, $grouped[11]['total']);
    }

    public function test_stay_type_stats_weekly_timeframe_groups_into_five_weeks_within_month(): void
    {
        $guest = Guest::create(['fname' => 'Charlie', 'lname' => 'Brown', 'phone' => '09173334455']);

        // Week 1 (Days 1 - 7): 6 Hours
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'booked_rooms' => [1],
            'check_in_datetime' => '2026-11-03 10:00:00',
            'check_out_datetime' => '2026-11-03 16:00:00',
        ]);

        // Week 3 (Days 15 - 21): 12 Hours
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 12,
            'booked_rooms' => [2],
            'check_in_datetime' => '2026-11-17 08:00:00',
            'check_out_datetime' => '2026-11-17 20:00:00',
        ]);

        // Week 5 (Days 29 - 30): 24 Hours
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'booked_rooms' => [3],
            'check_in_datetime' => '2026-11-29 14:00:00',
            'check_out_datetime' => '2026-11-30 12:00:00',
        ]);

        $response = $this->getJson('/api/admin/stay-type-stats?timeframe=weekly&year=2026&month=11&week=all');

        $response->assertOk();
        $grouped = $response->json('grouped_data');

        // Must be grouped into five weeks (Week 1 to Week 5)
        $this->assertCount(5, $grouped);
        $this->assertEquals('Week 1', $grouped[0]['label']);
        $this->assertEquals(1, $grouped[0]['counts']['6_hours']);

        $this->assertEquals('Week 2', $grouped[1]['label']);
        $this->assertEquals(0, $grouped[1]['total']);

        $this->assertEquals('Week 3', $grouped[2]['label']);
        $this->assertEquals(1, $grouped[2]['counts']['12_hours']);

        $this->assertEquals('Week 4', $grouped[3]['label']);
        $this->assertEquals(0, $grouped[3]['total']);

        $this->assertEquals('Week 5', $grouped[4]['label']);
        $this->assertEquals(1, $grouped[4]['counts']['24_hours']);

        // Querying specifically for Week 1 filters donut chart to Week 1
        $w1Response = $this->getJson('/api/admin/stay-type-stats?timeframe=weekly&year=2026&month=11&week=1');
        $w1Response->assertOk();
        $w1Response->assertJson([
            'total' => 1,
            'counts' => [0, 1, 0, 0],
        ]);
    }

    public function test_stay_type_stats_yearly_timeframe_groups_by_calendar_year(): void
    {
        $guest = Guest::create(['fname' => 'Diana', 'lname' => 'Prince', 'phone' => '09174445566']);

        // 2024 booking
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'booked_rooms' => [1],
            'check_in_datetime' => '2024-05-10 10:00:00',
            'check_out_datetime' => '2024-05-10 13:00:00',
        ]);

        // 2025 booking
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'booked_rooms' => [2],
            'check_in_datetime' => '2025-08-15 14:00:00',
            'check_out_datetime' => '2025-08-16 12:00:00',
        ]);

        $response = $this->getJson('/api/admin/stay-type-stats?timeframe=yearly&year=all');

        $response->assertOk();
        $grouped = $response->json('grouped_data');

        // Grouped by years 2022 to 2026
        $this->assertNotEmpty($grouped);
        $year2024 = collect($grouped)->firstWhere('year', 2024);
        $this->assertNotNull($year2024);
        $this->assertEquals(1, $year2024['counts']['3_hours']);

        $year2025 = collect($grouped)->firstWhere('year', 2025);
        $this->assertNotNull($year2025);
        $this->assertEquals(1, $year2025['counts']['24_hours']);

        // Querying for specific year 2024 filters chart to 2024 only
        $res2024 = $this->getJson('/api/admin/stay-type-stats?timeframe=yearly&year=2024');
        $res2024->assertOk();
        $res2024->assertJson([
            'total' => 1,
            'counts' => [1, 0, 0, 0],
        ]);
    }

    public function test_calendar_date_filter_updates_stay_type_stats(): void
    {
        $guest = Guest::create(['fname' => 'Evan', 'lname' => 'Wright', 'phone' => '09175556677']);

        // Booking on 2026-06-15 (6 Hours)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'booked_rooms' => [1],
            'check_in_datetime' => '2026-06-15 11:00:00',
            'check_out_datetime' => '2026-06-15 17:00:00',
        ]);

        // Booking on 2026-06-15 (24 Hours)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'overnight',
            'booked_rooms' => [2],
            'check_in_datetime' => '2026-06-15 14:00:00',
            'check_out_datetime' => '2026-06-16 12:00:00',
        ]);

        // Different day booking: 2026-06-20 (3 Hours)
        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'booked_rooms' => [3],
            'check_in_datetime' => '2026-06-20 10:00:00',
            'check_out_datetime' => '2026-06-20 13:00:00',
        ]);

        // 1. Pass exact date 2026-06-15 through calendar filter
        $response = $this->getJson('/api/admin/stay-type-stats?date=2026-06-15');

        $response->assertOk();
        $response->assertJson([
            'is_exact_date' => true,
            'selected_date' => '2026-06-15',
            'total' => 2,
            'counts' => [0, 1, 0, 1],
            'percentages' => [0.0, 50.0, 0.0, 50.0],
        ]);

        // The 3-hour booking on June 20 must NOT be included in June 15 results
        $this->assertEquals(0, $response->json('counts.0'));
        $this->assertEquals(1, $response->json('counts.1')); // 6 hours
        $this->assertEquals(0, $response->json('counts.2')); // 12 hours
        $this->assertEquals(1, $response->json('counts.3')); // 24 hours

        // 2. Exact date works alongside Weekly timeframe
        $weeklyWithDate = $this->getJson('/api/admin/stay-type-stats?timeframe=weekly&date=2026-06-15');
        $weeklyWithDate->assertOk();
        $weeklyWithDate->assertJson([
            'is_exact_date' => true,
            'selected_date' => '2026-06-15',
            'timeframe' => 'weekly',
            'total' => 2,
            'counts' => [0, 1, 0, 1],
        ]);

        // 3. Exact date works alongside Yearly timeframe
        $yearlyWithDate = $this->getJson('/api/admin/stay-type-stats?timeframe=yearly&date=2026-06-15');
        $yearlyWithDate->assertOk();
        $yearlyWithDate->assertJson([
            'is_exact_date' => true,
            'selected_date' => '2026-06-15',
            'timeframe' => 'yearly',
            'total' => 2,
            'counts' => [0, 1, 0, 1],
        ]);

        // 4. When date filter is cleared (empty date), restores data based on selected timeframe (June 2026 has all 3 bookings)
        $clearedDateResponse = $this->getJson('/api/admin/stay-type-stats?timeframe=monthly&year=2026&month=6');
        $clearedDateResponse->assertOk();
        $clearedDateResponse->assertJson([
            'is_exact_date' => false,
            'total' => 3,
            'counts' => [1, 1, 0, 1],
        ]);
    }

    public function test_stay_type_filters_do_not_interfere_with_revenue_statistics_or_overview_cards(): void
    {
        $guest = Guest::create(['fname' => 'Fiona', 'lname' => 'Gallagher', 'phone' => '09176667788']);

        Booking::create([
            'guest_id' => $guest->id,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
            'booked_rooms' => [1],
            'check_in_datetime' => '2026-11-21 10:00:00',
            'check_out_datetime' => '2026-11-21 13:00:00',
        ]);

        // Querying stay-type endpoint
        $stayTypeRes = $this->getJson('/api/admin/stay-type-stats?date=2026-11-21');
        $stayTypeRes->assertOk();

        // Revenue statistics endpoint remains intact and independent
        $revRes = $this->getJson('/api/admin/revenue-stats');
        $revRes->assertOk();
        $revRes->assertJsonStructure(['status', 'revenue_chart']);

        // Overview stats polling endpoint remains intact and independent
        $overviewStatsRes = $this->getJson('/api/admin/overview-stats');
        $overviewStatsRes->assertOk();
        $overviewStatsRes->assertJsonStructure(['occupancy_today', 'revenue_today', 'checkins_today', 'checkouts_today']);
    }
}
