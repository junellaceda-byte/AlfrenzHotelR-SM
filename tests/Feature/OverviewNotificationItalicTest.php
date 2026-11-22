<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewNotificationItalicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_overview_notifications_display_30_minute_alert_and_no_rooms_due_in_italic(): void
    {
        // Seed at least one room so dashboard renders cleanly
        Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $response = $this->get(route('dashboard.index'));

        $response->assertOk();

        // 30 minute alert subtitle is displayed in italic
        $response->assertSee('(30 minute alert due of guests check-out time)');
        $response->assertSee('class="text-muted fst-italic"', false);

        // "No rooms due within the next 30 minutes." message is displayed in italic
        $response->assertSee('No rooms due within the next 30 minutes.');
        $response->assertSee('class="text-muted small mb-0 py-2 fst-italic"', false);
    }
}
