<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        SystemSetting::set('date_format', 'M d, Y');
        SystemSetting::set('time_format', 'h:i A');

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@hotel.com',
                'password' => Hash::make('admin123'),
            ]
        );
    }

    public function test_admin_settings_page_renders_with_timestamp_formats_and_accman_link(): void
    {
        $response = $this->get('/admindashboard?section=settings');

        $response->assertStatus(200);
        $response->assertSee('System Settings');
        $response->assertSee('Timestamp Display Format');
        $response->assertSee('Time Display Mode');
        $response->assertSee('12-Hour Format');
        $response->assertSee('24-Hour Format');
        $response->assertSee('Go to Account Management');
    }

    public function test_account_management_page_contains_administrator_credentials_section(): void
    {
        $response = $this->get('/admindashboard?section=accman');

        $response->assertStatus(200);
        $response->assertSee('Administrator Credentials');
        $response->assertSee('admin_acc_username', false);
        $response->assertSee('admin_acc_password', false);
        $response->assertSee('admin_acc_password_confirmation', false);
        $response->assertSee('Save Administrator Credentials');
    }

    public function test_admin_can_update_credentials_from_account_management(): void
    {
        $response = $this->put(route('admin.credentials.update'), [
            'username' => 'newadminuser',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'section' => 'accman',
        ]);

        $response->assertRedirect('/admindashboard?section=accman');
        $response->assertSessionHas('success');

        $admin = User::first();
        $this->assertSame('newadminuser', $admin->username);
        $this->assertTrue(Hash::check('NewPassword123!', $admin->password));

        // Verify new credentials work for logging in
        $loginResponse = $this->post('/login', [
            'username' => 'newadminuser',
            'password' => 'NewPassword123!',
        ]);
        $loginResponse->assertRedirect('/admindashboard');
    }

    public function test_admin_credentials_update_requires_confirmation_when_password_provided(): void
    {
        $response = $this->from('/admindashboard?section=accman')->put(route('admin.credentials.update'), [
            'username' => 'admin',
            'password' => 'Mismatch123',
            'password_confirmation' => 'Different123',
            'section' => 'accman',
        ]);

        $response->assertRedirect('/admindashboard?section=accman');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_can_update_timestamp_display_format_to_24_hour(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $response->assertRedirect('/admindashboard?section=settings');
        $response->assertSessionHas('success');

        $this->assertSame('Y-m-d', SystemSetting::get('date_format'));
        $this->assertSame('H:i', SystemSetting::get('time_format'));

        $this->assertTrue(SystemFormatService::is24Hour());
        $this->assertSame('24-hour', SystemFormatService::timeFormatType());

        // Verify SystemFormatService formats correctly
        $sample = Carbon::parse('2026-11-22 17:30:00', 'Asia/Manila');
        $this->assertSame('17:30', SystemFormatService::formatTime($sample));
        $this->assertSame('2026-11-22', SystemFormatService::formatDate($sample));
        $this->assertSame('2026-11-22 17:30', SystemFormatService::formatDateTime($sample));
    }

    public function test_admin_can_update_timestamp_display_format_to_12_hour(): void
    {
        SystemSetting::set('time_format', 'H:i');

        $response = $this->put(route('admin.settings.update'), [
            'date_format' => 'M d, Y',
            'time_format' => 'h:i A',
        ]);

        $response->assertRedirect('/admindashboard?section=settings');
        $this->assertFalse(SystemFormatService::is24Hour());
        $this->assertSame('12-hour', SystemFormatService::timeFormatType());

        $sample = Carbon::parse('2026-11-22 17:30:00', 'Asia/Manila');
        $this->assertSame('05:30 PM', SystemFormatService::formatTime($sample));
    }

    public function test_receptionist_sidebar_contains_settings_navigation_item(): void
    {
        $this->actingAsReceptionist();

        $response = $this->get('/dashboardreceptionist');
        $response->assertStatus(200);
        $response->assertSee('SETTINGS');
        $response->assertSee(route('receptionist.settings'));
    }

    public function test_receptionist_settings_page_renders_with_profile_and_synchronized_timestamp_format(): void
    {
        $staff = Staff::create([
            'first_name' => 'Carla',
            'last_name' => 'Ramos',
            'username' => 'carlaramos',
            'email' => 'carla@hotel.com',
            'password' => Hash::make('password123'),
            'contact_number' => '09170001122',
        ]);

        $this->actingAsReceptionist($staff);

        SystemSetting::set('date_format', 'F d, Y');
        SystemSetting::set('time_format', 'H:i');

        $response = $this->get(route('receptionist.settings'));

        $response->assertStatus(200);
        $response->assertSee('System Settings');
        $response->assertSee('Receptionist Account Profile');
        $response->assertSee('Carla Ramos');
        $response->assertSee('@carlaramos');
        $response->assertSee('carla@hotel.com');
        $response->assertSee('09170001122');
        $response->assertSee('Timestamp Display Format');
        $response->assertSee('24-Hour');
        $response->assertSee('Synchronized with Administrator');

        // Administrator Credentials form MUST NOT be accessible on Receptionist page
        $response->assertDontSee('admin_acc_username');
        $response->assertDontSee('admin_acc_password');
        $response->assertDontSee('Save Administrator Credentials');
    }

    public function test_unauthenticated_user_cannot_access_receptionist_settings(): void
    {
        session()->flush();

        $response = $this->get(route('receptionist.settings'));
        $response->assertRedirect('/login');
    }
}
