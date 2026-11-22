<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_account_management_table_shows_email_and_removes_username_column(): void
    {
        $staff = Staff::create([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana.reyes@hotel.com',
            'contact_number' => '09123456789',
            'username' => 'anareyes',
            'password' => 'secretpassword',
        ]);

        $response = $this->get('/admindashboard?section=accman');
        $response->assertOk();

        // 1. Employee and actual Email are displayed
        $response->assertSee('Employee');
        $response->assertSee('Email');
        $response->assertSee('ana.reyes@hotel.com');
        $response->assertSee('Ana Reyes');

        // 2. Username column is removed from the table (similar to hidden Password column)
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Username</th>', false);
        $response->assertDontSee('col-staff-username', false);
        $response->assertDontSee('staff-cell-username', false);

        // 3. Password column is not exposed in the table
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Password</th>', false);
        $response->assertDontSee('col-staff-password', false);
        $response->assertDontSee('staff-cell-password', false);
    }

    public function test_username_is_stored_in_database_and_editable_in_edit_account_modal(): void
    {
        $staff = Staff::create([
            'first_name' => 'Ben',
            'last_name' => 'Santos',
            'email' => 'ben.santos@hotel.com',
            'contact_number' => '09223334444',
            'username' => 'bensantos',
            'password' => 'initialpass123',
        ]);

        $response = $this->get('/admindashboard?section=accman');
        $response->assertOk();

        // Edit Account modal contains username and email inputs with current values
        $response->assertSee('id="editStaffModal'.$staff->id.'"', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('value="bensantos"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('value="ben.santos@hotel.com"', false);

        // Update staff username and email through edit form submission
        $updateResponse = $this->put("/staff/{$staff->id}/update", [
            'first_name' => 'Ben',
            'last_name' => 'Santos',
            'email' => 'ben.updated@hotel.com',
            'contact_number' => '09223334444',
            'username' => 'bensantos_updated',
            'password' => 'initialpass123',
            'section' => 'accman',
        ]);

        $updateResponse->assertRedirect('/admindashboard?section=accman');

        $staff->refresh();

        // Username is updated and stored in the database
        $this->assertSame('bensantos_updated', $staff->username);
        // Email is updated and stored in the database
        $this->assertSame('ben.updated@hotel.com', $staff->email);

        // Table now displays updated email
        $viewResponse = $this->get('/admindashboard?section=accman');
        $viewResponse->assertSee('ben.updated@hotel.com');
    }

    public function test_email_validation_rejects_invalid_email_addresses(): void
    {
        // 1. Reject invalid email on store
        $storeResponse = $this->post('/staff/store', [
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'email' => 'invalid-email-format',
            'contact_number' => '09334445555',
            'username' => 'carlosm',
            'password' => 'securepass123',
            'section' => 'accman',
        ]);

        $storeResponse->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('staff', [
            'username' => 'carlosm',
        ]);

        // 2. Reject invalid email on update
        $staff = Staff::create([
            'first_name' => 'Diana',
            'last_name' => 'Cruz',
            'email' => 'diana.cruz@hotel.com',
            'contact_number' => '09445556666',
            'username' => 'dianacruz',
            'password' => 'pass123456',
        ]);

        $updateResponse = $this->put("/staff/{$staff->id}/update", [
            'first_name' => 'Diana',
            'last_name' => 'Cruz',
            'email' => 'not-a-valid-email',
            'contact_number' => '09445556666',
            'username' => 'dianacruz',
            'password' => 'pass123456',
            'section' => 'accman',
        ]);

        $updateResponse->assertSessionHasErrors('email');

        $staff->refresh();
        $this->assertSame('diana.cruz@hotel.com', $staff->email);
    }

    public function test_email_validation_accepts_valid_email_and_stores_in_database(): void
    {
        $response = $this->post('/staff/store', [
            'first_name' => 'Edward',
            'last_name' => 'Gonzales',
            'email' => 'edward.gonzales@alfrenzo.com',
            'contact_number' => '09556667777',
            'username' => 'edwardg',
            'password' => 'edwardpass123',
            'section' => 'accman',
        ]);

        $response->assertRedirect('/admindashboard?section=accman');

        $staff = Staff::where('username', 'edwardg')->first();
        $this->assertNotNull($staff);
        $this->assertSame('edward.gonzales@alfrenzo.com', $staff->email);
        $this->assertSame('edwardg', $staff->username);

        $this->assertDatabaseHas('staff', [
            'username' => 'edwardg',
            'email' => 'edward.gonzales@alfrenzo.com',
        ]);
    }
}
