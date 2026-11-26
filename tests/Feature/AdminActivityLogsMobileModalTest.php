<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminActivityLogsMobileModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function test_activities_view_hides_details_and_financials_on_mobile_and_includes_eye_modal(): void
    {
        ActivityLog::query()->delete();

        User::updateOrCreate(
            ['username' => 'admin'],
            ['user_id' => 'admin', 'password' => Hash::make('admin123')]
        );

        $staff = Staff::create([
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'username' => 'elena_frontdesk',
            'password' => 'secret123',
        ]);

        $log = ActivityLog::create([
            'staff_id' => $staff->id,
            'activity_type' => 'payment',
            'action' => 'payment_received',
            'guest_name' => 'Carlos Mendoza',
            'room_number' => 'Room 204',
            'booking_type' => 'overnight',
            'amount' => 2500.00,
            'payment_method' => 'gcash',
            'reference_number' => 'REF-99881',
            'description' => 'Overnight stay payment settled via GCash',
            'details' => ['receipt_no' => 'RCP-5521'],
        ]);

        $response = $this->withSession([
            'admin_id' => 1,
            'role' => 'admin',
        ])->get('/admindashboard?section=activities');

        $response->assertOk();
        $content = $response->getContent();

        // Mobile-hidden columns for Activity Details & Financials / Method
        $this->assertStringContainsString('class="py-3 px-3 fw-bold text-white small text-uppercase d-none d-md-table-cell"', $content);
        $this->assertStringContainsString('class="py-3 px-3 d-none d-md-table-cell"', $content);

        // Visible mobile columns (Type, Responsible User, Date & Time, Action Eye)
        $this->assertStringContainsString('activity-col-type', $content);
        $this->assertStringContainsString('activity-col-user', $content);
        $this->assertStringContainsString('activity-col-datetime', $content);
        $this->assertStringContainsString('activity-col-action', $content);

        // Eye icon button with record data-id
        $this->assertStringContainsString('btn-view-activity-details', $content);
        $this->assertStringContainsString('data-id="'.$log->id.'"', $content);

        // Modal included from Administrator.modals.salerep
        $this->assertStringContainsString('id="activityDetailsModal"', $content);
        $this->assertStringContainsString('id="modalActivityActorName"', $content);
        $this->assertStringContainsString('id="modalActivityDescription"', $content);
        $this->assertStringContainsString('id="modalActivityAmount"', $content);
        $this->assertStringContainsString('id="modalActivityPaymentMethod"', $content);

        // Initial payload contains complete record data
        $this->assertStringContainsString('Carlos Mendoza', $content);
        $this->assertStringContainsString('REF-99881', $content);
        $this->assertStringContainsString('RCP-5521', $content);
    }
}
