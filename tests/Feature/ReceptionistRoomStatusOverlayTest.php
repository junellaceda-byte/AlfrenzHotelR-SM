<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistRoomStatusOverlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_reserved_room_displays_visible_reserved_text_overlay(): void
    {
        $room = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'reserved',
        ]);

        $guest = Guest::create([
            'fname' => 'Alice',
            'lname' => 'Smith',
            'contact_no' => '09123456789',
        ]);

        Reservation::create([
            'guest_id' => $guest->id,
            'guest_name' => 'Alice Smith',
            'phone_number' => '09123456789',
            'number_of_rooms' => 1,
            'selected_rooms' => [(string) $room->id],
            'reservation_start_date' => now()->addDays(1),
            'reservation_end_date' => now()->addDays(2),
            'status' => 'reserved',
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();

        // 1. Reserved: Display a visible RESERVED text overlay using existing Reserved status color
        $response->assertSee('overlay-reserved', false);
        $response->assertSee('RESERVED', false);
    }

    public function test_under_maintenance_room_displays_visible_under_maintenance_text_overlay(): void
    {
        $room = Room::create([
            'room_number' => '102',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'available',
        ]);

        RoomBlock::create([
            'room_id' => $room->id,
            'reason' => 'Aircon repair',
            'start_date' => now()->subHour(),
            'end_date' => now()->addHours(5),
            'is_active' => true,
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();

        // 2. Under Maintenance: Display a visible UNDER MAINTENANCE text overlay
        $response->assertSee('overlay-maintenance', false);
        $response->assertSee('UNDER MAINTENANCE', false);
    }

    public function test_occupied_non_overdue_room_displays_occupied_text_overlay_and_add_amenities_button(): void
    {
        $room = Room::create([
            'room_number' => '103',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'fname' => 'Bob',
            'lname' => 'Jones',
            'contact_no' => '09123456789',
        ]);

        Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [(string) $room->id],
            'check_in_datetime' => now()->subHour(),
            'check_out_datetime' => now()->addHours(3),
            'status' => 'checked_in',
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();

        // 3. Occupied: Display a visible OCCUPIED text overlay
        $response->assertSee('overlay-occupied', false);
        $response->assertSee('OCCUPIED', false);

        // Add Amenities button present for non-overdue occupied room
        $response->assertSee('card-hover-action', false);
        $response->assertSee('btn-trigger-amenity', false);
        $response->assertSee('ADD AMENITIES', false);
    }

    public function test_overdue_occupied_room_overrides_occupied_with_overdue_and_disables_add_amenities_hover(): void
    {
        $room = Room::create([
            'room_number' => '104',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'available',
        ]);

        $guest = Guest::create([
            'fname' => 'Charlie',
            'lname' => 'Brown',
            'contact_no' => '09123456789',
        ]);

        Booking::create([
            'guest_id' => $guest->id,
            'booked_rooms' => [(string) $room->id],
            'check_in_datetime' => now()->subHours(6),
            'check_out_datetime' => now()->subHour(), // Past checkout -> Overdue
            'status' => 'checked_in',
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();

        // 4. Overdue: Display visible OVERDUE text overlay overriding occupied
        $response->assertSee('overlay-overdue', false);
        $response->assertSee('OVERDUE', false);

        // Does NOT render card-hover-action button inside the room tile
        $content = $response->getContent();
        // Check only inside hotelRoomGrid
        preg_match('/<div class="hotel-room-grid"[^>]*>(.*?)<\/div>\s*<\/div>/s', $content, $matches);
        $gridHtml = $matches[1] ?? '';
        $this->assertStringNotContainsString('card-hover-action', $gridHtml);
        $this->assertStringNotContainsString('btn-trigger-amenity', $gridHtml);
    }

    public function test_css_contains_status_overlay_typography_and_hover_replacement_rules(): void
    {
        $cssContent = file_get_contents(public_path('css/grid-overlay.css'));

        // Typography and alignment for overlays
        $this->assertStringContainsString('.overlay-occupied', $cssContent);
        $this->assertStringContainsString('.overlay-reserved', $cssContent);
        $this->assertStringContainsString('.overlay-maintenance', $cssContent);
        $this->assertStringContainsString('display: flex', $cssContent);
        $this->assertStringContainsString('justify-content: center', $cssContent);

        // Hover replacement rule for occupied room
        $this->assertStringContainsString('.hotel-room-card.is-occupied:hover .overlay-occupied', $cssContent);
        $this->assertStringContainsString('opacity: 0', $cssContent);

        // Overdue disables hover action and keeps overdue overlay visible
        $this->assertStringContainsString('.overlay-overdue', $cssContent);
        $this->assertStringContainsString('.card-hover-action', $cssContent);
    }

    public function test_scripts_dynamically_sets_status_overlay_text_for_all_statuses(): void
    {
        $scriptsContent = file_get_contents(resource_path('views/Receptionist/partials/scripts.blade.php'));

        $this->assertStringContainsString("'OCCUPIED'", $scriptsContent);
        $this->assertStringContainsString("'RESERVED'", $scriptsContent);
        $this->assertStringContainsString("'UNDER MAINTENANCE'", $scriptsContent);
        $this->assertStringContainsString("'OVERDUE'", $scriptsContent);
    }
}
