<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminActivityLogsPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function test_api_activities_pagination_returns_correct_pages_without_duplicate_or_skipped_records(): void
    {
        ActivityLog::query()->delete();

        $staff = Staff::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'username' => 'alice_staff',
            'password' => 'secret123',
        ]);

        // Create 35 distinct activity logs with sequential timestamps
        for ($i = 1; $i <= 35; $i++) {
            ActivityLog::create([
                'staff_id' => $staff->id,
                'activity_type' => 'booking',
                'action' => 'booking_created',
                'actor_name' => 'Alice Smith',
                'actor_username' => 'alice_staff',
                'guest_name' => "Guest {$i}",
                'room_number' => "Room {$i}",
                'amount' => 1000 + $i,
                'payment_method' => 'cash',
                'description' => "Booking {$i} description",
                'created_at' => now()->subMinutes(100 - $i),
            ]);
        }

        // Page 1: 15 records
        $response1 = $this->getJson('/api/admin/activities?page=1');
        $response1->assertOk()
            ->assertJson([
                'status' => 'success',
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 15,
                'total' => 35,
                'from' => 1,
                'to' => 15,
            ]);

        $data1 = $response1->json('data');
        $this->assertCount(15, $data1);
        $ids1 = collect($data1)->pluck('id')->all();

        // Page 2: 15 records
        $response2 = $this->getJson('/api/admin/activities?page=2');
        $response2->assertOk()
            ->assertJson([
                'status' => 'success',
                'current_page' => 2,
                'last_page' => 3,
                'per_page' => 15,
                'total' => 35,
                'from' => 16,
                'to' => 30,
            ]);

        $data2 = $response2->json('data');
        $this->assertCount(15, $data2);
        $ids2 = collect($data2)->pluck('id')->all();

        // Verify no duplicate records between page 1 and page 2
        $overlap1_2 = array_intersect($ids1, $ids2);
        $this->assertEmpty($overlap1_2, 'Page 1 and Page 2 must not have overlapping records');

        // Page 3: remaining 5 records
        $response3 = $this->getJson('/api/admin/activities?page=3');
        $response3->assertOk()
            ->assertJson([
                'status' => 'success',
                'current_page' => 3,
                'last_page' => 3,
                'per_page' => 15,
                'total' => 35,
                'from' => 31,
                'to' => 35,
            ]);

        $data3 = $response3->json('data');
        $this->assertCount(5, $data3);
        $ids3 = collect($data3)->pluck('id')->all();

        // Verify no duplicate records with page 3
        $overlap2_3 = array_intersect($ids2, $ids3);
        $overlap1_3 = array_intersect($ids1, $ids3);
        $this->assertEmpty($overlap2_3, 'Page 2 and Page 3 must not have overlapping records');
        $this->assertEmpty($overlap1_3, 'Page 1 and Page 3 must not have overlapping records');

        // Total combined distinct IDs across all 3 pages must be exactly 35 (no skipped records!)
        $allIds = array_merge($ids1, $ids2, $ids3);
        $this->assertCount(35, array_unique($allIds), 'All 35 records must be represented across pages without skipping');
    }

    public function test_api_activities_pagination_maintains_activity_type_and_who_filters(): void
    {
        ActivityLog::query()->delete();

        $staff1 = Staff::create([
            'first_name' => 'Receptionist',
            'last_name' => 'One',
            'username' => 'rec_one',
            'password' => 'secret123',
        ]);

        $staff2 = Staff::create([
            'first_name' => 'Receptionist',
            'last_name' => 'Two',
            'username' => 'rec_two',
            'password' => 'secret123',
        ]);

        // Create 20 payment records for staff1
        for ($i = 1; $i <= 20; $i++) {
            ActivityLog::create([
                'staff_id' => $staff1->id,
                'activity_type' => 'payment',
                'action' => 'payment_received',
                'actor_name' => 'Receptionist One',
                'actor_username' => 'rec_one',
                'guest_name' => "Payment Guest {$i}",
                'amount' => 500,
                'created_at' => now()->subMinutes(100 - $i),
            ]);
        }

        // Create 10 booking records for staff2
        for ($i = 1; $i <= 10; $i++) {
            ActivityLog::create([
                'staff_id' => $staff2->id,
                'activity_type' => 'booking',
                'action' => 'booking_created',
                'actor_name' => 'Receptionist Two',
                'actor_username' => 'rec_two',
                'guest_name' => "Booking Guest {$i}",
                'amount' => 1500,
                'created_at' => now()->subMinutes(50 - $i),
            ]);
        }

        // Query staff1 + payment on page 2: should have total 20, last_page 2, page 2 has 5 records
        $response = $this->getJson("/api/admin/activities?who={$staff1->id}&type=payment&page=2");
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'current_page' => 2,
                'last_page' => 2,
                'total' => 20,
                'from' => 16,
                'to' => 20,
            ]);

        $data = $response->json('data');
        $this->assertCount(5, $data);
        foreach ($data as $item) {
            $this->assertEquals($staff1->id, $item['staff_id']);
            $this->assertEquals('payment', $item['activity_type']);
        }
    }

    public function test_api_activities_pagination_maintains_search_filter(): void
    {
        ActivityLog::query()->delete();

        // Create 18 records matching "VIP", and 10 others
        for ($i = 1; $i <= 18; $i++) {
            ActivityLog::create([
                'activity_type' => 'booking',
                'action' => 'booking_created',
                'actor_name' => 'Receptionist',
                'guest_name' => "VIP Guest {$i}",
                'room_number' => 'Suite 101',
                'created_at' => now()->subMinutes(50 - $i),
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            ActivityLog::create([
                'activity_type' => 'booking',
                'action' => 'booking_created',
                'actor_name' => 'Receptionist',
                'guest_name' => "Standard Guest {$i}",
                'room_number' => 'Standard 202',
                'created_at' => now()->subMinutes(20 - $i),
            ]);
        }

        // Search for VIP on page 2: total matching should be 18, page 2 should have 3 records
        $response = $this->getJson('/api/admin/activities?search=VIP&page=2');
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'current_page' => 2,
                'last_page' => 2,
                'total' => 18,
                'from' => 16,
                'to' => 18,
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertStringContainsString('VIP', $item['guest_name']);
        }
    }

    public function test_admin_dashboard_activities_view_renders_pagination_controls(): void
    {
        ActivityLog::query()->delete();

        User::updateOrCreate(
            ['username' => 'admin'],
            ['user_id' => 'admin', 'password' => Hash::make('admin123')]
        );

        for ($i = 1; $i <= 25; $i++) {
            ActivityLog::create([
                'activity_type' => 'booking',
                'action' => 'booking_created',
                'actor_name' => 'Receptionist',
                'guest_name' => "Guest {$i}",
                'room_number' => "Room {$i}",
                'created_at' => now()->subMinutes(50 - $i),
            ]);
        }

        // Direct request to page 2 of activities
        $response = $this->withSession([
            'admin_id' => 1,
            'role' => 'admin',
        ])->get('/admindashboard?section=activities&page=2');

        $response->assertOk();
        $content = $response->getContent();

        // Checks table and pagination controls exist
        $this->assertStringContainsString('id="activityLogsPagination"', $content);
        $this->assertStringContainsString('id="activityLogsPaginationLinks"', $content);
        $this->assertStringContainsString('Showing 16 to 25 of 25 entries', $content);

        // Verify page-link with page 1 (Previous) is present and retains section=activities
        $this->assertStringContainsString('page=1', $content);
        $this->assertStringContainsString('section=activities', $content);
    }
}
