<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database tables exist
        Artisan::call('migrate');
    }

    public function test_receptionist_login_records_activity_log_exclusively()
    {
        $staff = Staff::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@hotel.com',
            'contact_number' => '09123456789',
            'username' => 'maria_receptionist',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'username' => 'maria_receptionist',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboardreceptionist');
        $this->assertEquals($staff->id, session('staff_id'));

        // Verify login log was created exclusively for this receptionist
        $log = ActivityLog::where('activity_type', 'login')->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->staff_id);
        $this->assertEquals('receptionist_login', $log->action);
        $this->assertEquals('maria_receptionist', $log->details['username']);
        $this->assertNotEmpty($log->details['login_date']);
        $this->assertNotEmpty($log->details['login_time']);
    }

    public function test_admin_login_does_not_create_receptionist_login_log()
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            ['user_id' => 'admin', 'password' => Hash::make('admin123')]
        );

        $initialLoginLogs = ActivityLog::where('activity_type', 'login')->count();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admindashboard');
        $this->assertEquals($initialLoginLogs, ActivityLog::where('activity_type', 'login')->count());
    }

    public function test_booking_creation_records_activity_log_with_receptionist()
    {
        $staff = Staff::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'username' => 'juan_staff',
            'password' => 'password123',
        ]);

        $room = Room::create([
            'room_number' => '99',
            'short_stay_rate' => 500,
            'overnight_rate' => 1500,
            'status' => 'available',
        ]);

        $now = now()->setTimezone('Asia/Manila');

        // Immediate Booking
        $response = $this->withSession(['staff_id' => $staff->id])->post('/bookings', [
            'fname' => 'Alice',
            'lname' => 'Wonderland',
            'phone' => '09112223344',
            'stay_type' => 'overnight',
            'with_breakfast' => 1,
            'check_in' => $now->format('Y-m-d H:i:s'),
            'check_out' => $now->copy()->addDay()->format('Y-m-d H:i:s'),
            'action_type' => 'check_in',
            'room_ids' => [$room->id],
        ]);

        $log = ActivityLog::where('activity_type', 'booking')
            ->where('action', 'booking_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->staff_id);
        $this->assertEquals('Alice Wonderland', $log->guest_name);
        $this->assertStringContainsString('99', $log->room_number);
    }

    public function test_reservation_creation_records_activity_log()
    {
        $staff = Staff::create([
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'username' => 'pedro_staff',
            'password' => 'password123',
        ]);

        $room = Room::create([
            'room_number' => '98',
            'short_stay_rate' => 500,
            'overnight_rate' => 1500,
            'status' => 'available',
        ]);

        $start = now()->addDays(2);
        $end = now()->addDays(3);

        $response = $this->withSession(['staff_id' => $staff->id])->post('/reservation', [
            'fname' => 'Bob',
            'lname' => 'Builder',
            'phone' => '09998887766',
            'check_in' => $start->format('Y-m-d H:i:s'),
            'check_out' => $end->format('Y-m-d H:i:s'),
            'stay_type' => 'overnight',
            'room_ids' => [$room->id],
            'deposit' => 500,
        ]);

        $log = ActivityLog::where('activity_type', 'booking')
            ->where('action', 'reservation_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->staff_id);
        $this->assertEquals('Bob Builder', $log->guest_name);
        $this->assertStringContainsString('98', $log->room_number);
    }

    public function test_cancellation_records_activity_log()
    {
        $staff = Staff::create([
            'first_name' => 'Clara',
            'last_name' => 'Oswald',
            'username' => 'clara_staff',
            'password' => 'password123',
        ]);

        $room = Room::create([
            'room_number' => '97',
            'short_stay_rate' => 500,
            'overnight_rate' => 1500,
            'status' => 'available',
        ]);

        $guest = Guest::create(['fname' => 'Charlie', 'lname' => 'Brown', 'phone' => '09123456780']);
        $reservation = Reservation::create([
            'guest_name' => 'Charlie Brown',
            'guest_id' => $guest->id,
            'phone_number' => '09123456780',
            'reservation_start_date' => now()->addDays(1),
            'reservation_end_date' => now()->addDays(2),
            'number_of_rooms' => 1,
            'selected_rooms' => [$room->id],
            'status' => 'reserved',
            'stay_type' => 'overnight',
        ]);

        $response = $this->withSession(['staff_id' => $staff->id])
            ->post("/reservation/{$reservation->id}/cancel");

        $log = ActivityLog::where('activity_type', 'cancellation')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->staff_id);
        $this->assertEquals('Charlie Brown', $log->guest_name);
        $this->assertEquals('reservation_cancelled', $log->action);
    }

    public function test_payment_processing_records_activity_log()
    {
        $staff = Staff::create([
            'first_name' => 'Donna',
            'last_name' => 'Noble',
            'username' => 'donna_staff',
            'password' => 'password123',
        ]);

        $room = Room::create([
            'room_number' => '96',
            'short_stay_rate' => 500,
            'overnight_rate' => 1500,
            'status' => 'occupied',
        ]);

        $guest = Guest::create(['fname' => 'Diana', 'lname' => 'Prince', 'phone' => '09123456781']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456781',
            'check_in_datetime' => now()->subHours(5),
            'check_out_datetime' => now()->addHours(1),
            'booked_rooms' => [$room->id],
            'stay_type' => 'overnight',
            'status' => 'checked_in',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-001',
            'room_charges' => 1500,
            'extra_charges_total' => 0,
            'grand_total' => 1500,
            'status' => 'unpaid',
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->withSession(['staff_id' => $staff->id])
            ->post("/billing/{$invoice->id}/process-payment", [
                'payment_method' => 'cash',
            ]);

        $log = ActivityLog::where('activity_type', 'payment')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->staff_id);
        $this->assertEquals('payment_processed', $log->action);
        $this->assertEquals('Diana Prince', $log->guest_name);
        $this->assertEquals(1500.00, (float) $log->amount);
    }

    public function test_overdue_is_automatically_recorded_as_system_generated()
    {
        $room = Room::create([
            'room_number' => '95',
            'short_stay_rate' => 500,
            'overnight_rate' => 1500,
            'status' => 'occupied',
        ]);

        $guest = Guest::create(['fname' => 'Oliver', 'lname' => 'Queen', 'phone' => '09123456782']);
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456782',
            'check_in_datetime' => now()->subHours(10),
            'check_out_datetime' => now()->subHours(2), // Overdue!
            'booked_rooms' => [$room->id],
            'stay_type' => 'overnight',
            'status' => 'checked_in',
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-OVERDUE-01',
            'room_charges' => 1500,
            'grand_total' => 1500,
            'status' => 'unpaid',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        // Trigger sync
        $count = ActivityLogService::syncOverdueBookings();
        $this->assertGreaterThanOrEqual(1, $count);

        $log = ActivityLog::where('activity_type', 'overdue')
            ->where('subject_id', $booking->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->staff_id, 'Overdue must be system-generated with NULL staff_id');
        $this->assertEquals('System Generated', $log->actor_name);
        $this->assertEquals('Oliver Queen', $log->guest_name);
        $this->assertStringContainsString('95', $log->room_number);

        // Ensure running sync again does not duplicate
        $secondCount = ActivityLogService::syncOverdueBookings();
        $this->assertEquals(0, $secondCount);
    }

    public function test_activities_api_filtering()
    {
        $staff1 = Staff::create([
            'first_name' => 'StaffOne',
            'last_name' => 'User',
            'username' => 'staffone',
            'password' => 'secret123',
        ]);
        $staff2 = Staff::create([
            'first_name' => 'StaffTwo',
            'last_name' => 'User',
            'username' => 'stafftwo',
            'password' => 'secret123',
        ]);

        ActivityLogService::logLogin($staff1);
        ActivityLogService::logLogin($staff2);

        // Filter by who = staff1->id
        $response = $this->getJson("/api/admin/activities?who={$staff1->id}&type=login");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals($staff1->id, $item['staff_id']);
        }

        // Filter by who = system
        $responseSystem = $this->getJson('/api/admin/activities?who=system');
        $responseSystem->assertOk();
        foreach ($responseSystem->json('data') as $item) {
            $this->assertTrue($item['is_system']);
        }
    }
}
