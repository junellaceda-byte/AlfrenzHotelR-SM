<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@hotel.com',
                'password' => Hash::make('admin123'),
            ]
        );
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Username');
        $response->assertSee('Password');
    }

    public function test_administrator_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admindashboard');
        $this->assertSame('admin', session('role'));
        $this->assertSame('admin', session('user_id'));
    }

    public function test_administrator_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username']);
        $this->assertNull(session('role'));
    }

    public function test_receptionist_can_authenticate_with_staff_credentials(): void
    {
        $staff = Staff::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'username' => 'mariasantos',
            'email' => 'maria@hotel.com',
            'password' => Hash::make('secret123'),
            'contact_number' => '09123456789',
        ]);

        $response = $this->post('/login', [
            'username' => 'mariasantos',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboardreceptionist');
        $this->assertSame('receptionist', session('role'));
        $this->assertSame($staff->id, session('staff_id'));
        $this->assertSame('Maria Santos', session('staff_name'));
    }

    public function test_receptionist_cannot_authenticate_with_invalid_password(): void
    {
        Staff::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'username' => 'mariasantos',
            'email' => 'maria@hotel.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'mariasantos',
            'password' => 'incorrectpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username']);
        $this->assertNull(session('role'));
    }

    public function test_receptionist_cannot_access_administrator_dashboard(): void
    {
        $this->actingAsReceptionist();

        $response = $this->get('/admindashboard');
        $response->assertRedirect('/dashboardreceptionist');
    }

    public function test_administrator_cannot_access_receptionist_dashboard(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/dashboardreceptionist');
        $response->assertRedirect('/admindashboard');
    }

    public function test_user_can_logout_and_session_is_cleared(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/logout');
        $response->assertRedirect('/login');
        $this->assertNull(session('role'));
        $this->assertNull(session('admin_id'));
    }
}
