<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TopbarPersonalizedGreetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_topbar_displays_authenticated_receptionist_username(): void
    {
        $staff = Staff::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'username' => 'alice_frontdesk',
            'email' => 'alice@hotel.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAsReceptionist($staff);

        $response = $this->get('/dashboardreceptionist');

        $response->assertOk();
        $response->assertSee('Good day, alice_frontdesk!');
    }

    public function test_receptionist_topbar_automatically_reflects_updated_username_from_database_record(): void
    {
        $staff = Staff::create([
            'first_name' => 'Bob',
            'last_name' => 'Taylor',
            'username' => 'initial_frontdesk',
            'email' => 'bob@hotel.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAsReceptionist($staff);

        $response1 = $this->get('/dashboardreceptionist');
        $response1->assertOk();
        $response1->assertSee('Good day, initial_frontdesk!');

        // Update username in database (as done in Administrator -> Account Management)
        $staff->update(['username' => 'updated_frontdesk']);

        $response2 = $this->get('/dashboardreceptionist');
        $response2->assertOk();
        $response2->assertSee('Good day, updated_frontdesk!');
        $response2->assertDontSee('Good day, initial_frontdesk!');
    }

    public function test_administrator_topbar_displays_authenticated_administrator_username(): void
    {
        $admin = User::create([
            'name' => 'Super Administrator',
            'username' => 'super_admin',
            'user_id' => 'super_admin',
            'email' => 'superadmin@hotel.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAsAdmin($admin);

        $response = $this->get('/admindashboard');

        $response->assertOk();
        $response->assertSee('Good day, super_admin!');
    }

    public function test_administrator_topbar_automatically_reflects_updated_credentials_after_save(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'username' => 'admin_v1',
            'user_id' => 'admin_v1',
            'email' => 'admin_v1@hotel.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAsAdmin($admin);

        $response1 = $this->get('/admindashboard');
        $response1->assertOk();
        $response1->assertSee('Good day, admin_v1!');

        // Save new administrator credentials via Administrator -> Account Management -> Administrator Credentials
        $updateResponse = $this->put('/admin/credentials', [
            'username' => 'admin_v2',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
            'section' => 'accman',
        ]);

        $updateResponse->assertRedirect('/admindashboard?section=accman');

        // Fetch dashboard after update
        $response2 = $this->get('/admindashboard?section=accman');
        $response2->assertOk();
        $response2->assertSee('Good day, admin_v2!');
        $response2->assertDontSee('Good day, admin_v1!');
    }

    public function test_staff_account_update_endpoint_updates_database_and_reflects_in_receptionist_topbar(): void
    {
        $admin = User::create([
            'name' => 'Manager',
            'username' => 'manager',
            'user_id' => 'manager',
            'email' => 'manager@hotel.com',
            'password' => Hash::make('secret123'),
        ]);

        $staff = Staff::create([
            'first_name' => 'Charlie',
            'last_name' => 'Day',
            'username' => 'staff_charlie',
            'email' => 'charlie@hotel.com',
            'password' => Hash::make('secret123'),
        ]);

        // Admin updates staff username in Account Management
        $this->actingAsAdmin($admin);
        $updateResponse = $this->put('/staff/'.$staff->id.'/update', [
            'first_name' => 'Charlie',
            'last_name' => 'Day',
            'email' => 'charlie@hotel.com',
            'username' => 'charlie_desk',
            'password' => 'secret123',
            'section' => 'accman',
        ]);
        $updateResponse->assertRedirect('/admindashboard?section=accman');

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'username' => 'charlie_desk',
        ]);

        // Receptionist logs in / views dashboard
        $this->actingAsReceptionist($staff->fresh());
        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();
        $response->assertSee('Good day, charlie_desk!');
    }
}
