<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffOnlineOfflineStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function test_employee_status_is_offline_by_default(): void
    {
        $staff = Staff::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@hotel.com',
            'contact_number' => '09123456781',
            'username' => 'johndoe_receptionist',
            'password' => 'secret123',
            'is_online' => false,
        ]);

        $this->assertFalse($staff->fresh()->is_online);

        // Verify API returns Offline status
        $response = $this->getJson('/api/admin/staff/statuses');
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $staffData = collect($response->json('data'))->firstWhere('id', $staff->id);
        $this->assertNotNull($staffData);
        $this->assertFalse($staffData['is_online']);
        $this->assertEquals('Offline', $staffData['status']);
        $this->assertEquals('bg-secondary', $staffData['badge_class']);

        // Verify Account Management page displays Offline badge
        $admin = User::first() ?? User::create([
            'username' => 'admin',
            'user_id' => 'admin',
            'password' => Hash::make('admin123'),
        ]);

        $pageResponse = $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
        ])->get('/admindashboard?section=accman');

        $pageResponse->assertOk();
        $pageResponse->assertSee('Offline');
        $pageResponse->assertSee('data-staff-id="'.$staff->id.'"', false);
    }

    public function test_employee_status_becomes_online_upon_login(): void
    {
        $staff = Staff::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@hotel.com',
            'contact_number' => '09123456782',
            'username' => 'maria_receptionist',
            'password' => 'secret123',
            'is_online' => false,
        ]);

        // Login as receptionist
        $loginResponse = $this->post('/login', [
            'username' => 'maria_receptionist',
            'password' => 'secret123',
        ]);

        $loginResponse->assertRedirect('/dashboardreceptionist');

        // Verify DB record is marked online
        $this->assertTrue((bool) $staff->fresh()->is_online);

        // Verify API reports employee as Online
        $apiResponse = $this->getJson('/api/admin/staff/statuses');
        $apiResponse->assertOk();

        $staffData = collect($apiResponse->json('data'))->firstWhere('id', $staff->id);
        $this->assertNotNull($staffData);
        $this->assertTrue($staffData['is_online']);
        $this->assertEquals('Online', $staffData['status']);
        $this->assertEquals('bg-success', $staffData['badge_class']);

        // Verify Account Management page displays Online badge
        $admin = User::first() ?? User::create([
            'username' => 'admin',
            'user_id' => 'admin',
            'password' => Hash::make('admin123'),
        ]);

        $pageResponse = $this->withSession([
            'admin_id' => $admin->id,
            'role' => 'admin',
        ])->get('/admindashboard?section=accman');

        $pageResponse->assertOk();
        $pageResponse->assertSee('Online');
        $pageResponse->assertSee('data-staff-id="'.$staff->id.'"', false);
    }

    public function test_employee_status_becomes_offline_upon_logout(): void
    {
        $staff = Staff::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@hotel.com',
            'contact_number' => '09123456783',
            'username' => 'juan_receptionist',
            'password' => 'secret123',
            'is_online' => false,
        ]);

        // Login first
        $this->post('/login', [
            'username' => 'juan_receptionist',
            'password' => 'secret123',
        ]);
        $this->assertTrue((bool) $staff->fresh()->is_online);

        // Logout
        $logoutResponse = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
        ])->post('/logout');

        $logoutResponse->assertRedirect('/login');

        // Verify DB record is now marked offline
        $this->assertFalse((bool) $staff->fresh()->is_online);

        // Verify API reports employee as Offline
        $apiResponse = $this->getJson('/api/admin/staff/statuses');
        $apiResponse->assertOk();

        $staffData = collect($apiResponse->json('data'))->firstWhere('id', $staff->id);
        $this->assertNotNull($staffData);
        $this->assertFalse($staffData['is_online']);
        $this->assertEquals('Offline', $staffData['status']);
        $this->assertEquals('bg-secondary', $staffData['badge_class']);
    }

    public function test_employee_status_becomes_offline_when_session_expires_due_to_inactivity(): void
    {
        $staff = Staff::create([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana.reyes@hotel.com',
            'contact_number' => '09123456784',
            'username' => 'ana_receptionist',
            'password' => 'secret123',
            'is_online' => true,
            'last_seen_at' => now()->subMinutes(35),
        ]);

        // Attempting to access dashboard with 35 minutes inactivity triggers session timeout
        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
            'receptionist_last_activity' => now()->subMinutes(35)->timestamp,
        ])->get('/dashboardreceptionist');

        $response->assertRedirect('/login');
        $response->assertSessionHas('timeout');

        // Verify employee is marked offline in DB
        $this->assertFalse((bool) $staff->fresh()->is_online);

        // Verify API endpoint reports employee as Offline
        $apiResponse = $this->getJson('/api/admin/staff/statuses');
        $apiResponse->assertOk();

        $staffData = collect($apiResponse->json('data'))->firstWhere('id', $staff->id);
        $this->assertNotNull($staffData);
        $this->assertFalse($staffData['is_online']);
        $this->assertEquals('Offline', $staffData['status']);
    }

    public function test_receptionist_sidebar_logout_modal_links_to_logout_route(): void
    {
        $staff = Staff::create([
            'first_name' => 'Sidebar',
            'last_name' => 'Tester',
            'email' => 'sidebar.tester@hotel.com',
            'username' => 'sidebar_tester',
            'password' => 'secret123',
            'is_online' => true,
        ]);

        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
        ])->get('/dashboardreceptionist');

        $response->assertOk();
        // The modal confirm logout link must point to /logout
        $response->assertSee('href="'.route('logout').'"', false);
    }

    public function test_each_employee_status_is_updated_independently_when_one_logs_out(): void
    {
        $staff1 = Staff::create([
            'first_name' => 'Employee',
            'last_name' => 'One',
            'email' => 'emp1@hotel.com',
            'username' => 'emp1_user',
            'password' => 'secret123',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $staff2 = Staff::create([
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'email' => 'emp2@hotel.com',
            'username' => 'emp2_user',
            'password' => 'secret123',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        // Employee 1 logs out
        $this->withSession([
            'staff_id' => $staff1->id,
            'role' => 'receptionist',
        ])->get(route('logout'));

        // Employee 1 must be offline
        $this->assertFalse((bool) $staff1->fresh()->is_online);

        // Employee 2 MUST remain online (independent tracking)
        $this->assertTrue((bool) $staff2->fresh()->is_online);

        // API should reflect Employee 1 Offline, Employee 2 Online
        $apiResponse = $this->getJson('/api/admin/staff/statuses');
        $apiResponse->assertOk();

        $data1 = collect($apiResponse->json('data'))->firstWhere('id', $staff1->id);
        $data2 = collect($apiResponse->json('data'))->firstWhere('id', $staff2->id);

        $this->assertEquals('Offline', $data1['status']);
        $this->assertFalse($data1['is_online']);

        $this->assertEquals('Online', $data2['status']);
        $this->assertTrue($data2['is_online']);
    }

    public function test_navigating_to_login_screen_updates_staff_status_to_offline(): void
    {
        $staff = Staff::create([
            'first_name' => 'Direct',
            'last_name' => 'Login',
            'email' => 'direct.login@hotel.com',
            'username' => 'direct_login_user',
            'password' => 'secret123',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        // Employee hits /login
        $response = $this->withSession([
            'staff_id' => $staff->id,
            'role' => 'receptionist',
        ])->get('/login');

        $response->assertOk();
        $this->assertFalse((bool) $staff->fresh()->is_online);
    }
}
