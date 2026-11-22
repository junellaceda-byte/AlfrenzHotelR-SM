<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_store_staff_with_securely_stored_password(): void
    {
        $response = $this->post('/staff/store', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'gmail' => 'maria.santos@gmail.com',
            'contact_number' => '09123456789',
            'username' => 'mariasantos',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/admindashboard?section=staff');

        $staff = Staff::where('username', 'mariasantos')->first();
        $this->assertNotNull($staff);
        $this->assertSame('Maria', $staff->first_name);
        $this->assertSame('Santos', $staff->last_name);
        $this->assertSame('maria.santos@gmail.com', $staff->gmail);
        $this->assertSame('09123456789', $staff->contact_number);

        // Password is encrypted in database raw storage (not plain text)
        $rawPassword = $staff->getRawOriginal('password');
        $this->assertNotSame('secret123', $rawPassword);

        // Decrypting the raw storage yields the original plain text password
        $this->assertSame('secret123', Crypt::decryptString($rawPassword));

        // Model attribute returns the plain text password
        $this->assertSame('secret123', $staff->password);
    }

    public function test_staff_table_does_not_contain_password_column(): void
    {
        Staff::create([
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'gmail' => 'elena.reyes@gmail.com',
            'contact_number' => '09223334444',
            'username' => 'elenareyes',
            'password' => 'supersecretpass',
        ]);

        $response = $this->get('/admindashboard?section=staff');
        $response->assertOk();

        // Active table headers
        $response->assertSee('Employee');
        $response->assertSee('Email');
        $response->assertSee('Contact Number');
        $response->assertSee('Status');
        $response->assertSee('Last active');

        // Username column header and cell must NOT exist in the Account Management table (similar to Password)
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Username</th>', false);
        $response->assertDontSee('col-staff-username', false);
        $response->assertDontSee('staff-cell-username', false);

        // Password column header must NOT exist in the Staff table
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4">Password</th>', false);
        $response->assertDontSee('col-staff-password', false);

        // Staff table body must NOT expose the password column cell
        $response->assertDontSee('staff-cell-password', false);
    }

    public function test_edit_staff_modal_displays_saved_password_as_plain_text(): void
    {
        $staff = Staff::create([
            'first_name' => 'Carlos',
            'last_name' => 'Garcia',
            'gmail' => 'carlos.garcia@gmail.com',
            'contact_number' => '09887776655',
            'username' => 'carlosg',
            'password' => 'myplainpass123',
        ]);

        $response = $this->get('/admindashboard?section=staff');
        $response->assertOk();

        $response->assertSee('id="editStaffModal'.$staff->id.'"', false);

        // The password field in the Edit modal must display the plain text password and be type="text"
        $response->assertSee('<input type="text" name="password" class="form-control" value="myplainpass123" required>', false);

        // Raw encrypted value must not be shown in the input value
        $rawPassword = $staff->getRawOriginal('password');
        $response->assertDontSee('value="'.$rawPassword.'"', false);
    }

    public function test_admin_can_modify_password_in_edit_modal_and_save_updated_value(): void
    {
        $staff = Staff::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gmail' => 'juan@gmail.com',
            'contact_number' => '09111111111',
            'username' => 'juandc',
            'password' => 'initialpass123',
        ]);

        // Administrator directly modifies the password to 'updatedpass456'
        $response = $this->put("/staff/{$staff->id}/update", [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gmail' => 'juan@gmail.com',
            'contact_number' => '09111111111',
            'username' => 'juandc',
            'password' => 'updatedpass456',
        ]);

        $response->assertRedirect('/admindashboard?section=staff');

        $staff->refresh();

        // Model attribute returns the updated plain text password
        $this->assertSame('updatedpass456', $staff->password);

        // Database raw storage is securely encrypted and decrypts to the updated password
        $rawPassword = $staff->getRawOriginal('password');
        $this->assertNotSame('updatedpass456', $rawPassword);
        $this->assertSame('updatedpass456', Crypt::decryptString($rawPassword));

        // When retrieving the view, the edit modal now reflects the updated plain text password
        $viewResponse = $this->get('/admindashboard?section=staff');
        $viewResponse->assertSee('<input type="text" name="password" class="form-control" value="updatedpass456" required>', false);
    }

    public function test_add_staff_modal_contains_all_input_fields(): void
    {
        $response = $this->get('/admindashboard?section=staff');
        $response->assertOk();

        $response->assertSee('id="addStaffModal"', false);
        $response->assertSee('name="first_name"', false);
        $response->assertSee('name="last_name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="contact_number"', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_staff_view_script_is_transferred_to_scripts_partial(): void
    {
        // 1. staff.blade.php should not contain inline <script> tags
        $staffBladeContent = file_get_contents(resource_path('views/Administrator/content/staff.blade.php'));
        $this->assertStringNotContainsString('<script', $staffBladeContent);

        // 2. scripts.blade.php should contain the transferred Staff mobile and row action logic
        $scriptsBladeContent = file_get_contents(resource_path('views/Administrator/partials/scripts.blade.php'));
        $this->assertStringContainsString('staffMobileEditBtn', $scriptsBladeContent);
        $this->assertStringContainsString('staffMobileDeleteBtn', $scriptsBladeContent);
        $this->assertStringContainsString('staffMobileSelectionNotice', $scriptsBladeContent);
        $this->assertStringContainsString('staff-row', $scriptsBladeContent);
        $this->assertStringContainsString('executeStaffEdit', $scriptsBladeContent);
        $this->assertStringContainsString('executeStaffDelete', $scriptsBladeContent);

        // 3. When loading the staff dashboard section, scripts are properly included in the response
        $response = $this->get('/admindashboard?section=staff');
        $response->assertOk();
        $response->assertSee('staffMobileEditBtn', false);
        $response->assertSee('staffMobileDeleteBtn', false);
        $response->assertSee('executeStaffEdit', false);
        $response->assertSee('executeStaffDelete', false);
    }
}
