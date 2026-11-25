<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceptionistSettingsTextColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_receptionist_settings_text_on_beige_green_background_is_black(): void
    {
        $staff = Staff::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'username' => 'mariasantos',
            'email' => 'maria@hotel.com',
            'password' => Hash::make('password123'),
            'contact_number' => '09181234567',
        ]);

        $this->actingAsReceptionist($staff);

        SystemSetting::set('date_format', 'M d, Y');
        SystemSetting::set('time_format', 'h:i A');

        $response = $this->get(route('receptionist.settings'));

        $response->assertOk();

        // 1. Check that Beige Green #F2F6D0 backgrounds are present
        $response->assertSee('background-color: #F2F6D0', false);

        // 2. Check that text on Beige Green is styled with Black #000000
        $response->assertSee('color: #000000', false);

        // 3. Confirm profile values are rendered
        $response->assertSee('Maria Santos');
        $response->assertSee('@mariasantos');
        $response->assertSee('maria@hotel.com');
        $response->assertSee('09181234567');

        // 4. Confirm timestamp settings are displayed
        $response->assertSee('Synchronized System Timestamp');
        $response->assertSee('Receptionist Account Profile');
        $response->assertSee('Timestamp Display Format');
    }
}
