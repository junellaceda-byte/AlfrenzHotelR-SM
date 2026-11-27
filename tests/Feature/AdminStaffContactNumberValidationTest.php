<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffContactNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // POST /staff/store – contact_number validation
    // ---------------------------------------------------------------------------

    public function test_add_staff_accepts_valid_philippine_contact_number(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'contact_number' => '+639171234567',   // +63 + 10 digits
            'username' => 'juantest_'.uniqid(),
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_add_staff_accepts_valid_international_contact_number(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'contact_number' => '+12125551234',    // +1 + 10 digits (US)
            'username' => 'janetest_'.uniqid(),
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_add_staff_rejects_missing_contact_number(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'No',
            'last_name' => 'Contact',
            'email' => 'nocontact@example.com',
            'username' => 'nocontact_'.uniqid(),
            'password' => 'secret123',
            // contact_number intentionally omitted
        ]);

        $response->assertSessionHasErrors('contact_number');
    }

    public function test_add_staff_rejects_contact_number_without_country_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'Bad',
            'last_name' => 'Format',
            'email' => 'bad@example.com',
            'contact_number' => '09171234567',   // no leading + country code
            'username' => 'badformat_'.uniqid(),
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('contact_number');
    }

    public function test_add_staff_rejects_contact_number_too_short(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'Short',
            'last_name' => 'Number',
            'email' => 'short@example.com',
            'contact_number' => '+63123',         // only 3 digits after country code
            'username' => 'shortnumber_'.uniqid(),
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('contact_number');
    }

    public function test_add_staff_rejects_contact_number_with_letters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff/store', [
            'first_name' => 'Alpha',
            'last_name' => 'Num',
            'email' => 'alpha@example.com',
            'contact_number' => '+63917ABC1234',
            'username' => 'alphanum_'.uniqid(),
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('contact_number');
    }

    // ---------------------------------------------------------------------------
    // PUT /staff/{id}/update – contact_number validation
    // ---------------------------------------------------------------------------

    public function test_edit_staff_accepts_valid_contact_number(): void
    {
        $this->actingAsAdmin();

        $staff = Staff::create([
            'first_name' => 'EditJuan',
            'last_name' => 'Dela Cruz',
            'email' => 'editjuan@example.com',
            'username' => 'editjuan_'.uniqid(),
            'password' => 'secret123',
            'contact_number' => '+639171234567',
        ]);

        $response = $this->put("/staff/{$staff->id}/update", [
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'contact_number' => '+639999999999',
            'username' => $staff->username,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'contact_number' => '+639999999999',
        ]);
    }

    public function test_edit_staff_rejects_missing_contact_number(): void
    {
        $this->actingAsAdmin();

        $staff = Staff::create([
            'first_name' => 'Missing',
            'last_name' => 'Contact',
            'email' => 'missingcontact@example.com',
            'username' => 'missingcontact_'.uniqid(),
            'password' => 'secret123',
            'contact_number' => '+639171234567',
        ]);

        $response = $this->put("/staff/{$staff->id}/update", [
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'username' => $staff->username,
            'password' => 'secret123',
            // contact_number omitted
        ]);

        $response->assertSessionHasErrors('contact_number');
    }

    // ---------------------------------------------------------------------------
    // View rendering – modals contain country code dropdown
    // ---------------------------------------------------------------------------

    public function test_admin_account_management_page_contains_country_code_dropdown(): void
    {
        $this->actingAsAdmin();

        $staff = Staff::create([
            'first_name' => 'Dropdown',
            'last_name' => 'Test',
            'email' => 'dropdown@example.com',
            'username' => 'dropdowntest_'.uniqid(),
            'password' => 'secret123',
            'contact_number' => '+639171234567',
        ]);

        $response = $this->get('/admindashboard?section=accman');

        $response->assertStatus(200);
        $response->assertSee('addStaffCountryCode', false);
        $response->assertSee('+63', false);
        $response->assertSee('editStaffCountryCode'.$staff->id, false);
    }

    public function test_edit_modal_pre_fills_local_number_without_country_code(): void
    {
        $this->actingAsAdmin();

        $staff = Staff::create([
            'first_name' => 'Prefill',
            'last_name' => 'Test',
            'email' => 'prefill@example.com',
            'username' => 'prefilltest_'.uniqid(),
            'password' => 'secret123',
            'contact_number' => '+639171234567',
        ]);

        $response = $this->get('/admindashboard?section=accman');

        $response->assertStatus(200);
        // Local number (without +63 country code) should appear as the input value
        $response->assertSee('9171234567', false);
    }
}
