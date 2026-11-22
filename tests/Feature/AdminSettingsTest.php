<?php

namespace Tests\Feature;

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

    public function test_admin_settings_page_renders_with_current_settings_and_username(): void
    {
        $response = $this->get('/admindashboard?section=settings');

        $response->assertStatus(200);
        $response->assertSee('System Settings');
        $response->assertSee('Administrator Credentials');
        $response->assertSee('value="admin"', false);
        $response->assertSee('M d, Y');
        $response->assertSee('h:i A');
    }

    public function test_admin_can_update_system_formats(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'username' => 'admin',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $response->assertRedirect('/admindashboard?section=settings');
        $response->assertSessionHas('success');

        $this->assertSame('Y-m-d', SystemSetting::get('date_format'));
        $this->assertSame('H:i', SystemSetting::get('time_format'));

        // Verify SystemFormatService uses the newly updated formats
        $date = Carbon::parse('2026-11-20 15:45:00', 'Asia/Manila');
        $this->assertSame('2026-11-20', SystemFormatService::formatDate($date));
        $this->assertSame('15:45', SystemFormatService::formatTime($date));
    }

    public function test_admin_can_update_username(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'username' => 'superadmin',
            'date_format' => 'M d, Y',
            'time_format' => 'h:i A',
        ]);

        $response->assertRedirect('/admindashboard?section=settings');
        $response->assertSessionHas('success');

        $user = User::where('username', 'superadmin')->first();
        $this->assertNotNull($user);
        $this->assertSame('superadmin', $user->username);
    }

    public function test_admin_can_update_password_with_secure_laravel_hashing(): void
    {
        $newPassword = 'NewSecretPassword123!';

        $response = $this->put(route('admin.settings.update'), [
            'username' => 'admin',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'date_format' => 'M d, Y',
            'time_format' => 'h:i A',
        ]);

        $response->assertRedirect('/admindashboard?section=settings');
        $response->assertSessionHas('success');

        $user = User::where('username', 'admin')->first();
        $this->assertNotNull($user);

        // Raw password must not be stored in plain text
        $this->assertNotSame($newPassword, $user->getRawOriginal('password'));

        // Verified with Hash::check()
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check('OldPasswordOrWrong', $user->password));
    }

    public function test_password_update_requires_matching_confirmation(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'username' => 'admin',
            'password' => 'ValidPassword123',
            'password_confirmation' => 'MismatchPassword',
            'date_format' => 'M d, Y',
            'time_format' => 'h:i A',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_update_requires_minimum_length_of_six_characters(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'username' => 'admin',
            'password' => '12345',
            'password_confirmation' => '12345',
            'date_format' => 'M d, Y',
            'time_format' => 'h:i A',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_system_settings_api_returns_formats_and_real_time_timestamps(): void
    {
        SystemSetting::set('date_format', 'F d, Y');
        SystemSetting::set('time_format', 'h:i:s A');

        $response = $this->get(route('api.system.settings'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'date_format',
            'time_format',
            'formatted_date',
            'formatted_time',
            'server_time',
        ]);

        $response->assertJson([
            'date_format' => 'F d, Y',
            'time_format' => 'h:i:s A',
        ]);
    }
}
