<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\SystemSetting;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceptionistDateTimeFormatDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = Staff::create([
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'username' => 'elenacruz',
            'email' => 'elena@hotel.com',
            'password' => Hash::make('password123'),
            'contact_number' => '09171234567',
        ]);
    }

    public function test_receptionist_settings_displays_human_readable_date_time_formats_and_mode_label(): void
    {
        SystemSetting::set('date_format', 'M d, Y');
        SystemSetting::set('time_format', 'h:i A');

        $this->actingAsReceptionist($this->staff);

        $response = $this->get(route('receptionist.settings'));

        $response->assertOk();
        $response->assertSee('HH:MM AM/PM');
        $response->assertDontSee('12-Hour Clock (with AM/PM)');
        $response->assertSee('HH:MM');
        $response->assertSee('MMM DD, YYYY');
        $response->assertDontSee('>h:i A<', false);
        $response->assertDontSee('>M d, Y<', false);
    }

    public function test_system_format_service_maps_display_formats_correctly(): void
    {
        $this->assertSame('MMM DD, YYYY', SystemFormatService::displayDateFormat('M d, Y'));
        $this->assertSame('MMMM DD, YYYY', SystemFormatService::displayDateFormat('F d, Y'));
        $this->assertSame('YYYY-MM-DD', SystemFormatService::displayDateFormat('Y-m-d'));
        $this->assertSame('MM/DD/YYYY', SystemFormatService::displayDateFormat('m/d/Y'));
        $this->assertSame('DD/MM/YYYY', SystemFormatService::displayDateFormat('d/m/Y'));

        $this->assertSame('HH:MM', SystemFormatService::displayTimeFormat('h:i A'));
        $this->assertSame('HH:MM:SS', SystemFormatService::displayTimeFormat('h:i:s A'));
        $this->assertSame('HH:MM', SystemFormatService::displayTimeFormat('H:i'));
        $this->assertSame('HH:MM:SS', SystemFormatService::displayTimeFormat('H:i:s'));

        $this->assertSame('HH:MM AM/PM', SystemFormatService::timeFormatModeLabel('h:i A'));
        $this->assertSame('24-Hour Standard Time', SystemFormatService::timeFormatModeLabel('H:i'));

        $sample = Carbon::parse('2026-11-26 22:35:00', 'Asia/Manila');
        SystemSetting::set('date_format', 'M d, Y');
        SystemSetting::set('time_format', 'h:i A');
        $this->assertSame('10:35 PM', SystemFormatService::formatTime($sample));
        $this->assertSame('Nov 26, 2026', SystemFormatService::formatDate($sample));
    }

    public function test_only_administrator_can_change_system_wide_formats_and_changes_apply_to_receptionist(): void
    {
        // Receptionist cannot update system settings
        $this->actingAsReceptionist($this->staff);
        $receptionistAttempt = $this->put(route('admin.settings.update'), [
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);
        $receptionistAttempt->assertRedirect('/dashboardreceptionist');
        $this->assertSame('M d, Y', SystemFormatService::dateFormat());
        $this->assertSame('h:i A', SystemFormatService::timeFormat());

        // Administrator can update system settings
        $this->actingAsAdmin();
        $adminUpdate = $this->put(route('admin.settings.update'), [
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ]);
        $adminUpdate->assertRedirect('/admindashboard?section=settings');
        $adminUpdate->assertSessionHas('success');

        // Verify changes automatically apply to Receptionist settings and API
        $this->actingAsReceptionist($this->staff);
        $receptionistResponse = $this->get(route('receptionist.settings'));
        $receptionistResponse->assertOk();
        $receptionistResponse->assertSee('YYYY-MM-DD');
        $receptionistResponse->assertSee('HH:MM:SS');
        $receptionistResponse->assertSee('24-Hour Standard Time');

        $apiResponse = $this->getJson(route('api.system.settings'));
        $apiResponse->assertOk()->assertJson([
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'display_date_format' => 'YYYY-MM-DD',
            'display_time_format' => 'HH:MM:SS',
            'is_24_hour' => true,
            'time_mode_label' => '24-Hour Standard Time',
        ]);
    }
}
