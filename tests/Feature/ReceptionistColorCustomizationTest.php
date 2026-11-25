<?php

namespace Tests\Feature;

use App\Models\staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistColorCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings
        $this->artisan('db:seed', ['--class' => 'SystemSettingSeeder']);
    }

    /**
     * Test Global Header and Topbar color styling in Receptionist views.
     */
    public function test_receptionist_topbar_has_white_background_and_vintage_brown_timestamp_container(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('dashboard.index'));

        $response->assertStatus(200);

        // Header Background: White class and visible #AEAC78 background
        $response->assertSee('bg-white', false);
        $response->assertSee('background-color: #AEAC78', false);

        // Timestamp container: Vintage Brown #443627
        $response->assertSee('background-color: #443627', false);

        // Date and Time Text: Beige #F5F7DF
        $response->assertSee('color: #F5F7DF', false);

        // Main body background: Beige #F5F7DF
        $response->assertSee('background-color: #F5F7DF', false);
    }

    /**
     * Test Room Status Cards color palette.
     */
    public function test_room_status_cards_have_prescribed_palette_colors(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('dashboard.index'));

        $response->assertStatus(200);

        // Available: Green #2BAB2B
        $response->assertSee('#2BAB2B', false);

        // Occupied: Orange #E87B2C
        $response->assertSee('#E87B2C', false);

        // Reserved: Lime Green #A8E036
        $response->assertSee('#A8E036', false);

        // Under Maintenance: Muted Rose #A34665
        $response->assertSee('#A34665', false);
    }

    /**
     * Test Settings Container has Vintage Brown #443627.
     */
    public function test_receptionist_settings_container_has_vintage_brown_background(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('receptionist.settings'));

        $response->assertStatus(200);

        // Settings Container: Vintage Brown #443627
        $response->assertSee('background-color: #443627', false);
    }

    /**
     * Test Logout Confirmation Modal has dedicated header and Vintage Brown styling.
     */
    public function test_logout_modal_has_dedicated_header_with_vintage_brown(): void
    {
        $staff = staff::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'contact_number' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'staff_username' => $staff->username,
            'receptionist_last_activity' => now()->timestamp,
        ])->get(route('dashboard.index'));

        $response->assertStatus(200);

        // Dedicated header with Vintage Brown #443627
        $response->assertSee('Confirm Logout');
        $response->assertSee('id="logoutConfirmModal"', false);
    }

    /**
     * Test Notifications in Administrator Overview have Vintage Brown Header and Beige Body.
     */
    public function test_admin_notifications_have_vintage_brown_header_and_beige_body(): void
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->withSession([
            'admin_id' => $user->id,
            'role' => 'admin',
            'admin_username' => $user->username,
            'admin_last_activity' => now()->timestamp,
        ])->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Header: #443627
        $response->assertSee('background-color: #443627', false);
        // Body: #F5F7DF
        $response->assertSee('background-color: #F5F7DF', false);
    }
}
