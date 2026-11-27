<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoomStatusColorsAndGridTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'SystemSettingSeeder']);
    }

    /**
     * Test that Administrator Room Summary Cards use the specified background colors and contrasting text/icons.
     */
    public function test_admin_room_summary_cards_use_specified_palette(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
            'admin_username' => $admin->username,
            'admin_last_activity' => now()->timestamp,
        ])->actingAs($admin)->get('/admindashboard?section=rooms');

        $response->assertOk();

        // Available: Green #2BAB2B background with white text/icon
        $response->assertSee('background-color: #2BAB2B', false);
        $response->assertSee('text-white', false);

        // Under Maintenance: Muted Rose #A34665 background with white text/icon
        $response->assertSee('background-color: #A34665', false);

        // Occupied: Orange #E87B2C background with white text/icon
        $response->assertSee('background-color: #E87B2C', false);

        // Reserved: Lime Green #A8E036 background with dark contrasting text/icon
        $response->assertSee('background-color: #A8E036', false);
        $response->assertSee('color: #1c2a08', false);
    }

    /**
     * Test that Administrator Room Status Grid has OCCUPIED text and does NOT have Add Amenities hover button.
     */
    public function test_admin_room_grid_has_occupied_overlay_without_add_amenities_hover(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $room = Room::create([
            'room_number' => '101',
            'short_stay_rate' => 500,
            'overnight_rate' => 2000,
            'status' => 'occupied',
        ]);

        $response = $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
            'admin_username' => $admin->username,
            'admin_last_activity' => now()->timestamp,
        ])->actingAs($admin)->get('/admindashboard?section=rooms');

        $response->assertOk();

        // Occupied overlay has OCCUPIED text
        $response->assertSee('overlay-occupied');
        $response->assertSee('OCCUPIED');

        // Add Amenities hover action button must remain exclusive to Receptionist side
        $response->assertDontSee('btn-card-amenities');
        $response->assertDontSee('ADD AMENITIES');
        $response->assertDontSee('<div class="card-hover-action">', false);
    }

    /**
     * Test that the Room Status Grid does NOT display the room ID label (e.g. "ID: #1").
     */
    public function test_room_status_grid_does_not_show_room_id_label(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'adminuser2',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        Room::create([
            'room_number' => '5',
            'short_stay_rate' => 400,
            'overnight_rate' => 1500,
            'status' => 'available',
        ]);

        $response = $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
            'admin_username' => $admin->username,
            'admin_last_activity' => now()->timestamp,
        ])->actingAs($admin)->get('/admindashboard?section=rooms');

        $response->assertOk();

        // Room ID label must NOT be visible in the grid
        $response->assertDontSee('ID: #', false);

        // Room number must still be shown (padded)
        $response->assertSee('Room 05', false);
    }

    /**
     * Test grid overlay CSS contains gray occupied background, blue reserved, and keeps occupied overlay on hover.
     */
    public function test_grid_overlay_and_rooms_css_definitions(): void
    {
        $gridOverlayCss = file_get_contents(public_path('css/grid-overlay.css'));
        $roomsCss = file_get_contents(public_path('css/Administrator/rooms.css'));

        // Occupied is gray
        $this->assertStringContainsString('rgba(100, 100, 100', $gridOverlayCss);

        // Reserved is blue
        $this->assertStringContainsString('rgba(33, 115, 237', $gridOverlayCss);

        // Under Maintenance preserved
        $this->assertStringContainsString('.overlay-maintenance', $gridOverlayCss);

        // Occupied overlay remains visible on hover on Administrator side
        $this->assertStringContainsString('.hotel-room-card.is-occupied:hover .overlay-occupied', $roomsCss);
        $this->assertStringContainsString('opacity: 1 !important', $roomsCss);

        // Add Amenities hover action is hidden on Administrator side
        $this->assertStringContainsString('.card-hover-action', $roomsCss);
        $this->assertStringContainsString('display: none !important', $roomsCss);

        // Gray background preserved on hover
        $this->assertStringContainsString('.hotel-room-card.is-occupied:hover .room-image-wrapper', $roomsCss);
        $this->assertStringContainsString('#646464', $roomsCss);
    }
}
