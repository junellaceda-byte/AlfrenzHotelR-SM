<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guest = Guest::create([
            'fname' => 'Carlos',
            'lname' => 'Santana',
            'contact_no' => '09123456789',
            'email' => 'carlos@example.com',
        ]);
    }

    public function test_admin_rooms_page_renders_with_database_rooms_and_kpis(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        // Available room
        $room1 = Room::create([
            'room_number' => '10',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        // Occupied room
        $room2 = Room::create([
            'room_number' => '11',
            'short_stay_rate' => 700.00,
            'overnight_rate' => 2500.00,
            'status' => 'available',
        ]);

        Booking::create([
            'guest_id' => $this->guest->id,
            'check_in_datetime' => $now->copy()->subHour(),
            'check_out_datetime' => $now->copy()->addHours(3),
            'stay_type' => 'short_stay',
            'booked_rooms' => [$room2->id],
            'total_amount' => 700.00,
            'deposit' => 700.00,
            'status' => 'checked_in',
        ]);

        // Maintenance room
        $room3 = Room::create([
            'room_number' => '12',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        RoomBlock::create([
            'room_id' => $room3->id,
            'reason' => 'Aircon repair',
            'start_date' => $now->copy()->subMinutes(10),
            'end_date' => $now->copy()->addDay(),
            'is_active' => true,
        ]);

        $response = $this->get('/admindashboard?section=rooms');

        $response->assertOk();
        $response->assertSee('Room Management');
        $response->assertSee('Room 10');
        $response->assertSee('Room 11');
        $response->assertSee('Room 12');
        $response->assertSee('Available');
        $response->assertSee('Occupied');
        $response->assertSee('Under Maintenance');
        $response->assertSee('Aircon repair');
    }

    public function test_admin_can_create_new_room(): void
    {
        $response = $this->post(route('admin.rooms.store'), [
            'room_number' => '25',
            'short_stay_rate' => 850.00,
            'overnight_rate' => 3000.00,
        ]);

        $response->assertRedirect('/admindashboard?section=rooms');

        $this->assertDatabaseHas('rooms', [
            'room_number' => '25',
            'short_stay_rate' => 850.00,
            'overnight_rate' => 3000.00,
            'status' => 'available',
        ]);
    }

    public function test_new_room_created_by_admin_is_immediately_accessible_to_receptionist(): void
    {
        // Admin creates a room
        $this->post(route('admin.rooms.store'), [
            'room_number' => '30',
            'short_stay_rate' => 900.00,
            'overnight_rate' => 3200.00,
        ]);

        // Receptionist dashboard loads rooms from database
        $receptionistResponse = $this->get('/dashboardreceptionist');
        $receptionistResponse->assertOk();
        $receptionistResponse->assertSee('Room 30');
    }

    public function test_admin_can_update_room_details(): void
    {
        $room = Room::create([
            'room_number' => '40',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $response = $this->put(route('admin.rooms.update', $room->id), [
            'room_number' => '40A',
            'short_stay_rate' => 750.00,
            'overnight_rate' => 2600.00,
            'status' => 'available',
        ]);

        $response->assertRedirect('/admindashboard?section=rooms');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'room_number' => '40A',
            'short_stay_rate' => 750.00,
            'overnight_rate' => 2600.00,
        ]);
    }

    public function test_admin_cannot_delete_occupied_room(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        $room = Room::create([
            'room_number' => '50',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'occupied',
        ]);

        Booking::create([
            'guest_id' => $this->guest->id,
            'check_in_datetime' => $now->copy()->subHour(),
            'check_out_datetime' => $now->copy()->addDay(),
            'stay_type' => 'overnight',
            'booked_rooms' => [$room->id],
            'total_amount' => 2200.00,
            'deposit' => 1000.00,
            'status' => 'checked_in',
        ]);

        $response = $this->delete(route('admin.rooms.destroy', $room->id));

        $response->assertRedirect('/admindashboard?section=rooms');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
        ]);
    }

    public function test_admin_can_delete_available_room_without_bookings(): void
    {
        $room = Room::create([
            'room_number' => '60',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $response = $this->delete(route('admin.rooms.destroy', $room->id));

        $response->assertRedirect('/admindashboard?section=rooms');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    public function test_receptionist_can_reserve_room_created_by_admin(): void
    {
        $room = Room::create([
            'room_number' => '70',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        // Receptionist reserves the admin-created room
        $response = $this->post(route('reservation.store'), [
            'fname' => 'Fernando',
            'lname' => 'Poe',
            'phone' => '09181234567',
            'check_in' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'check_out' => now()->addDays(4)->format('Y-m-d\TH:i'),
            'stay_type' => 'overnight',
            'room_ids' => [$room->id],
            'deposit' => 1000.00,
        ]);

        $this->assertDatabaseHas('reservations', [
            'guest_name' => 'Fernando Poe',
            'deposit' => 1000.00,
        ]);
    }
}
