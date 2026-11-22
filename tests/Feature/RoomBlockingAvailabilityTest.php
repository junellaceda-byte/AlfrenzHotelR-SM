<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomBlockingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Room $room2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();

        $this->room1 = Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);

        $this->room2 = Room::create([
            'room_number' => '02',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'available',
        ]);
    }

    public function test_room_with_active_block_overlapping_booking_window_is_excluded_from_available_rooms_api(): void
    {
        $blockStart = Carbon::parse('2026-11-20 10:00:00');
        $blockEnd = Carbon::parse('2026-11-20 16:00:00');

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $blockStart,
            'end_date' => $blockEnd,
            'reason' => 'Aircon repair',
            'is_active' => true,
        ]);

        // Query stay window that overlaps with the block (11:00 to 14:00)
        $response = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 11:00:00',
            'end_date' => '2026-11-20 14:00:00',
        ]));

        $response->assertOk();
        $roomIds = collect($response->json())->pluck('id')->all();

        $this->assertNotContains($this->room1->id, $roomIds, 'Blocked room must be excluded when window overlaps.');
        $this->assertContains($this->room2->id, $roomIds, 'Unblocked room must remain available.');
    }

    public function test_room_with_active_block_is_selectable_outside_blocking_period_before(): void
    {
        $blockStart = Carbon::parse('2026-11-20 12:00:00');
        $blockEnd = Carbon::parse('2026-11-20 18:00:00');

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $blockStart,
            'end_date' => $blockEnd,
            'reason' => 'Painting',
            'is_active' => true,
        ]);

        // Query stay window before the block (08:00 to 11:00)
        $response = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 08:00:00',
            'end_date' => '2026-11-20 11:00:00',
        ]));

        $response->assertOk();
        $roomIds = collect($response->json())->pluck('id')->all();

        $this->assertContains($this->room1->id, $roomIds, 'Room must remain selectable before blocking period.');
        $this->assertContains($this->room2->id, $roomIds);
    }

    public function test_room_with_active_block_is_selectable_outside_blocking_period_after(): void
    {
        $blockStart = Carbon::parse('2026-11-20 10:00:00');
        $blockEnd = Carbon::parse('2026-11-20 14:00:00');

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $blockStart,
            'end_date' => $blockEnd,
            'reason' => 'Plumbing',
            'is_active' => true,
        ]);

        // Query stay window after the block (15:00 to 18:00)
        $response = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 15:00:00',
            'end_date' => '2026-11-20 18:00:00',
        ]));

        $response->assertOk();
        $roomIds = collect($response->json())->pluck('id')->all();

        $this->assertContains($this->room1->id, $roomIds, 'Room must remain selectable after blocking period.');
    }

    public function test_room_with_block_meeting_at_exact_boundaries_is_selectable(): void
    {
        $blockStart = Carbon::parse('2026-11-20 10:00:00');
        $blockEnd = Carbon::parse('2026-11-20 14:00:00');

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $blockStart,
            'end_date' => $blockEnd,
            'reason' => 'Cleaning',
            'is_active' => true,
        ]);

        // Checkout exactly at block start
        $response1 = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 07:00:00',
            'end_date' => '2026-11-20 10:00:00',
        ]));
        $response1->assertOk();
        $this->assertContains($this->room1->id, collect($response1->json())->pluck('id')->all());

        // Checkin exactly at block end
        $response2 = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 14:00:00',
            'end_date' => '2026-11-20 17:00:00',
        ]));
        $response2->assertOk();
        $this->assertContains($this->room1->id, collect($response2->json())->pluck('id')->all());
    }

    public function test_inactive_maintenance_block_does_not_exclude_room(): void
    {
        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => Carbon::parse('2026-11-20 10:00:00'),
            'end_date' => Carbon::parse('2026-11-20 14:00:00'),
            'reason' => 'Cancelled work',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 11:00:00',
            'end_date' => '2026-11-20 13:00:00',
        ]));

        $response->assertOk();
        $roomIds = collect($response->json())->pluck('id')->all();
        $this->assertContains($this->room1->id, $roomIds, 'Inactive block must not exclude the room.');
    }

    public function test_room_marked_as_under_maintenance_status_is_excluded_when_overlapping_and_selectable_outside(): void
    {
        $this->room1->update(['status' => 'under_maintenance']);

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => Carbon::parse('2026-11-20 10:00:00'),
            'end_date' => Carbon::parse('2026-11-20 14:00:00'),
            'reason' => 'Renovation',
            'is_active' => true,
        ]);

        // Overlapping window -> excluded
        $resOverlapping = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 11:00:00',
            'end_date' => '2026-11-20 13:00:00',
        ]));
        $this->assertNotContains($this->room1->id, collect($resOverlapping->json())->pluck('id')->all());

        // Outside window -> selectable
        $resOutside = $this->getJson(route('api.available-rooms', [
            'start_date' => '2026-11-20 15:00:00',
            'end_date' => '2026-11-20 18:00:00',
        ]));
        $this->assertContains($this->room1->id, collect($resOutside->json())->pluck('id')->all());
    }

    public function test_reservation_creation_rejects_room_overlapping_maintenance_block(): void
    {
        $checkIn = Carbon::today()->addDays(2)->setHour(14)->setMinute(0);
        $checkOut = $checkIn->copy()->addHours(24);

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $checkIn->copy()->subHours(2),
            'end_date' => $checkIn->copy()->addHours(4),
            'reason' => 'Emergency repair',
            'is_active' => true,
        ]);

        $response = $this->post(route('reservation.store'), [
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'phone' => '09123456789',
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'overnight',
            'guests' => 1,
        ]);

        $response->assertSessionHasErrors('room_ids');
        $this->assertDatabaseMissing('reservations', [
            'phone_number' => '09123456789',
        ]);
    }

    public function test_reservation_creation_succeeds_for_room_outside_maintenance_block(): void
    {
        $checkIn = Carbon::today()->addDays(2)->setHour(14)->setMinute(0);
        $checkOut = $checkIn->copy()->addHours(24);

        // Block is scheduled for the day after checkout
        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $checkOut->copy()->addHours(2),
            'end_date' => $checkOut->copy()->addHours(6),
            'reason' => 'Scheduled inspection',
            'is_active' => true,
        ]);

        $response = $this->post(route('reservation.store'), [
            'fname' => 'Maria',
            'lname' => 'Santos',
            'phone' => '09987654321',
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'overnight',
            'guests' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', [
            'phone_number' => '09987654321',
        ]);
    }

    public function test_booking_creation_rejects_room_overlapping_maintenance_block(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $checkIn = $now->copy()->startOfHour()->addMinute();
        $checkOut = $checkIn->copy()->addHours(3);

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $checkIn->copy()->subHour(),
            'end_date' => $checkIn->copy()->addHours(2),
            'reason' => 'Deep cleaning',
            'is_active' => true,
        ]);

        $response = $this->post(route('booking.store'), [
            'fname' => 'Carlos',
            'lname' => 'Reyes',
            'phone' => '09112223333',
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 3,
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut->format('Y-m-d H:i:s'),
            'action_type' => 'check_in',
            'guests' => 1,
        ]);

        $response->assertSessionHasErrors('room_ids');
        $this->assertDatabaseMissing('bookings', [
            'phone' => '09112223333',
        ]);
    }

    public function test_booking_creation_succeeds_for_room_outside_maintenance_block(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $checkIn = $now->copy()->startOfHour()->addMinute();
        $checkOut = $checkIn->copy()->addHours(3);

        // Block is scheduled 5 hours after checkout
        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $checkOut->copy()->addHours(5),
            'end_date' => $checkOut->copy()->addHours(8),
            'reason' => 'Light replacement',
            'is_active' => true,
        ]);

        $response = $this->post(route('booking.store'), [
            'fname' => 'Elena',
            'lname' => 'Gomez',
            'phone' => '09445556666',
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 3,
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut->format('Y-m-d H:i:s'),
            'action_type' => 'check_in',
            'guests' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', [
            'phone' => '09445556666',
        ]);
    }

    public function test_multi_night_reservation_rejects_room_when_block_falls_on_subsequent_day(): void
    {
        $checkIn = Carbon::today()->addDays(2)->setHour(14)->setMinute(0);
        $checkOut = $checkIn->copy()->addDays(3); // 3-night reservation

        // Maintenance block is placed on day 2 of the stay
        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $checkIn->copy()->addDay()->setHour(10)->setMinute(0),
            'end_date' => $checkIn->copy()->addDay()->setHour(16)->setMinute(0),
            'reason' => 'Mid-stay maintenance',
            'is_active' => true,
        ]);

        $response = $this->post(route('reservation.store'), [
            'fname' => 'Roberto',
            'lname' => 'Cruz',
            'phone' => '09778889999',
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'overnight',
            'guests' => 1,
        ]);

        $response->assertSessionHasErrors('room_ids');
        $this->assertDatabaseMissing('reservations', [
            'phone_number' => '09778889999',
        ]);
    }

    public function test_overview_card_displays_total_number_of_blocked_rooms_and_grid_displays_formatted_period(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $start = $now->copy()->addMinutes(10);
        $end = $start->copy()->addDays(1)->setHour(17)->setMinute(0);

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'HVAC overhaul',
            'is_active' => true,
        ]);

        $response = $this->get(route('dashboard.index'));
        $response->assertOk();

        // 1. Overview card: Under Maintenance count
        $response->assertSee('<div class="kpi-card-number text-secondary" id="kpi-maintenance">1</div>', false);

        // 2. Room Status Grid: affected room identified as Under Maintenance
        $response->assertSee('data-status="under_maintenance"', false);
        $response->assertSee('Under Maintenance');

        // 3. Room Status Grid: blocking period formatted as Start: M/D/YYYY, Time and End: M/D/YYYY, Time
        $expectedFormat = 'Start: '.$start->format('n/j/Y, g:i A').' and End: '.$end->format('n/j/Y, g:i A');
        $response->assertSee($expectedFormat);
    }

    public function test_dashboard_api_returns_formatted_blocking_period_and_kpi(): void
    {
        $now = now()->setTimezone('Asia/Manila');
        $start = $now->copy()->addMinutes(15);
        $end = $start->copy()->addHours(6);

        RoomBlock::create([
            'room_id' => $this->room1->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Window replacement',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/dashboard/notifications');
        $response->assertOk();

        $expectedFormat = 'Start: '.$start->format('n/j/Y, g:i A').' and End: '.$end->format('n/j/Y, g:i A');

        $this->assertEquals(1, $response->json('kpi.maintenance'));
        $this->assertEquals('under_maintenance', $response->json("room_statuses.{$this->room1->id}"));
        $this->assertEquals($expectedFormat, $response->json("room_blocking_periods.{$this->room1->id}"));
    }
}
