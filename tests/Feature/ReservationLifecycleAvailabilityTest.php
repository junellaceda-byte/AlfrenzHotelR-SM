<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationLifecycleAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Room $room1;

    private Room $room2;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_reserving_room_updates_reserved_kpi_card_and_labels_room_without_in_out_times(): void
    {
        $arrival = Carbon::now()->addHours(2);

        $response = $this->post(route('reservation.store'), [
            'fname' => 'Maria',
            'lname' => 'Santos',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $arrival->copy()->addHours(6)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', [
            'guest_name' => 'Maria Santos',
            'status' => 'reserved',
        ]);

        // Receptionist dashboard Overview & Grid
        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();

        // Reserved KPI card displays total reserved rooms (1)
        $dashboard->assertViewHas('reservedRooms', 1);
        $dashboard->assertSee('id="kpi-reserved">1<', false);

        // Room 01 status is reserved in view data
        $rooms = $dashboard->viewData('rooms');
        $r1 = $rooms->firstWhere('id', $this->room1->id);
        $this->assertSame('reserved', $r1->status);

        // Grid contains Reserved label
        $dashboard->assertSee('Reserved');

        // Room Status Grid does NOT contain IN/OUT times for this reserved room
        $content = $dashboard->getContent();
        preg_match('/<div class="hotel-room-grid"[^>]*>.*?<\/div>\s*<\/div>/s', $content, $matches);
        $gridHtml = $matches[0] ?? $content;
        $this->assertStringContainsString('Room 01', $gridHtml);
        $this->assertStringNotContainsString('<div><span class="text-muted">IN:</span>', $gridHtml);
    }

    public function test_room_with_active_reservation_is_excluded_from_available_rooms_during_period(): void
    {
        $arrival = Carbon::now()->addDays(1)->setTime(14, 0);
        $departure = $arrival->copy()->addHours(6); // 14:00 to 20:00

        $this->post(route('reservation.store'), [
            'fname' => 'Pedro',
            'lname' => 'Penduko',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $departure->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
        ])->assertSessionHasNoErrors();

        // 1. Overlapping window: 15:00 to 18:00 -> Room 01 must NOT be available
        $overlapRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $arrival->copy()->addHour()->toDateTimeString(),
            'end_date' => $departure->copy()->subHour()->toDateTimeString(),
        ]));
        $overlapRes->assertOk();
        $roomIds = collect($overlapRes->json())->pluck('id')->all();
        $this->assertNotContains($this->room1->id, $roomIds, 'Room with active reservation must be excluded during reserved period.');
        $this->assertContains($this->room2->id, $roomIds);

        // 2. Non-overlapping window: before the reservation (08:00 to 12:00) -> Room 01 must be available
        $freeRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $arrival->copy()->subHours(6)->toDateTimeString(),
            'end_date' => $arrival->copy()->subHours(2)->toDateTimeString(),
        ]));
        $freeRes->assertOk();
        $freeRoomIds = collect($freeRes->json())->pluck('id')->all();
        $this->assertContains($this->room1->id, $freeRoomIds, 'Room must remain available outside reserved period.');
    }

    public function test_checking_in_changes_status_to_occupied_with_in_out_times_and_frees_non_overlapping_times(): void
    {
        $arrival = Carbon::now()->addHours(1);
        $departure = $arrival->copy()->addHours(6);

        $this->post(route('reservation.store'), [
            'fname' => 'Clara',
            'lname' => 'Reyes',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $departure->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
        ])->assertSessionHasNoErrors();

        $reservation = Reservation::where('guest_name', 'Clara Reyes')->firstOrFail();

        // Check in the guest
        $checkInRes = $this->post(route('reservation.checkIn', $reservation->id));
        $checkInRes->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_in',
        ]);

        $this->assertDatabaseHas('bookings', [
            'guest_id' => $reservation->guest_id,
            'status' => 'checked_in',
        ]);

        // Dashboard overview & grid check
        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();

        // Reserved card decrements, Occupied increments
        $dashboard->assertViewHas('reservedRooms', 0);
        $dashboard->assertViewHas('occupiedRooms', 1);

        // Room 01 is now occupied
        $rooms = $dashboard->viewData('rooms');
        $r1 = $rooms->firstWhere('id', $this->room1->id);
        $this->assertSame('occupied', $r1->status);

        // Grid contains Occupied and IN / OUT times
        $content = $dashboard->getContent();
        $this->assertStringContainsString('Occupied', $content);
        $this->assertStringContainsString('<div><span class="text-muted">IN:</span>', $content);
        $this->assertStringContainsString('<div><span class="text-muted">OUT:</span>', $content);

        // Availability check: original future window is no longer treated as pending reservation conflict;
        // availability is now strictly based on actual occupancy period (now to now+6h).
        $afterOccupancy = Carbon::now()->addHours(8);
        $afterRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $afterOccupancy->toDateTimeString(),
            'end_date' => $afterOccupancy->copy()->addHours(4)->toDateTimeString(),
        ]));
        $afterRes->assertOk();
        $availableAfter = collect($afterRes->json())->pluck('id')->all();
        $this->assertContains($this->room1->id, $availableAfter, 'Room must be selectable after actual occupancy period ends.');

        // During actual occupancy (now to now+2h), room must still be occupied/conflicting
        $duringOccupancy = $this->getJson(route('api.available-rooms', [
            'start_date' => Carbon::now()->addMinutes(10)->toDateTimeString(),
            'end_date' => Carbon::now()->addHours(2)->toDateTimeString(),
        ]));
        $duringOccupancy->assertOk();
        $availableDuring = collect($duringOccupancy->json())->pluck('id')->all();
        $this->assertNotContains($this->room1->id, $availableDuring, 'Room must conflict during actual occupancy.');
    }

    public function test_reserving_multiple_rooms_updates_reserved_kpi_card_to_total_reserved_rooms(): void
    {
        $arrival = Carbon::now()->addDays(2)->setTime(14, 0);

        $this->post(route('reservation.store'), [
            'fname' => 'Family',
            'lname' => 'Group',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $arrival->copy()->addHours(24)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id, $this->room2->id],
            'stay_type' => 'overnight',
        ])->assertSessionHasNoErrors();

        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();

        // Reserved KPI displays 2 (total number of reserved rooms)
        $dashboard->assertViewHas('reservedRooms', 2);
        $dashboard->assertSee('id="kpi-reserved">2<', false);
    }

    public function test_reserving_through_booking_store_with_reserved_action_type(): void
    {
        $arrival = Carbon::now()->addDays(1)->setTime(15, 0);

        $response = $this->post(route('booking.store'), [
            'fname' => 'Elena',
            'lname' => 'Dela Cruz',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $arrival->copy()->addHours(6)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
            'action_type' => 'reserved',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', [
            'guest_name' => 'Elena Dela Cruz',
            'status' => 'reserved',
        ]);

        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();
        $dashboard->assertViewHas('reservedRooms', 1);

        $rooms = $dashboard->viewData('rooms');
        $this->assertSame('reserved', $rooms->firstWhere('id', $this->room1->id)->status);
    }

    public function test_booking_process_reservation_excludes_room_during_overlapping_period_and_allows_non_conflicting(): void
    {
        $arrival = Carbon::now()->addDays(3)->setTime(10, 0);
        $departure = $arrival->copy()->addHours(6); // 10:00 to 16:00 3 days from now

        // Reserve Room 1 through the Booking process (action_type = reserved)
        $this->post(route('booking.store'), [
            'fname' => 'Roberto',
            'lname' => 'Gomez',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $departure->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
            'action_type' => 'reserved',
        ])->assertSessionHasNoErrors();

        // 1. Overlapping window with reserved room -> Room 1 must be excluded
        $overlapRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $arrival->copy()->addHours(2)->toDateTimeString(),
            'end_date' => $departure->copy()->addHours(2)->toDateTimeString(),
        ]));
        $overlapRes->assertOk();
        $overlapIds = collect($overlapRes->json())->pluck('id')->all();
        $this->assertNotContains($this->room1->id, $overlapIds, 'Reserved room through booking process must be excluded during overlapping period.');
        $this->assertContains($this->room2->id, $overlapIds);

        // 2. Non-conflicting window before reserved room -> Room 1 must be selectable
        $beforeRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $arrival->copy()->subHours(6)->toDateTimeString(),
            'end_date' => $arrival->toDateTimeString(),
        ]));
        $beforeRes->assertOk();
        $beforeIds = collect($beforeRes->json())->pluck('id')->all();
        $this->assertContains($this->room1->id, $beforeIds, 'Reserved room must be selectable before reserved period.');

        // 3. Non-conflicting window after reserved room -> Room 1 must be selectable
        $afterRes = $this->getJson(route('api.available-rooms', [
            'start_date' => $departure->toDateTimeString(),
            'end_date' => $departure->copy()->addHours(6)->toDateTimeString(),
        ]));
        $afterRes->assertOk();
        $afterIds = collect($afterRes->json())->pluck('id')->all();
        $this->assertContains($this->room1->id, $afterIds, 'Reserved room must be selectable after reserved period.');

        // 4. Overlap rejection when attempting to reserve Room 1 again for conflicting window
        $conflictSubmit = $this->post(route('booking.store'), [
            'fname' => 'Second',
            'lname' => 'Guest',
            'phone' => '09999999999',
            'check_in' => $arrival->copy()->addHour()->format('Y-m-d H:i:s'),
            'check_out' => $departure->copy()->addHour()->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
            'action_type' => 'reserved',
        ]);
        $conflictSubmit->assertSessionHasErrors('room_ids');

        // 5. Non-conflicting submit succeeds
        $nonConflictSubmit = $this->post(route('booking.store'), [
            'fname' => 'Third',
            'lname' => 'Guest',
            'phone' => '09888888888',
            'check_in' => $departure->copy()->addHour()->format('Y-m-d H:i:s'),
            'check_out' => $departure->copy()->addHours(7)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
            'action_type' => 'reserved',
        ]);
        $nonConflictSubmit->assertSessionHasNoErrors();
    }

    public function test_reserved_rooms_not_counted_in_occupied_card_until_checked_in_and_grid_filter_works(): void
    {
        $arrival = Carbon::now()->addDays(1)->setTime(12, 0);
        $departure = $arrival->copy()->addHours(24);

        // Reserve Room 1 via booking.store
        $this->post(route('booking.store'), [
            'fname' => 'ReservationOne',
            'lname' => 'Guest',
            'phone' => '09111111111',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $departure->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'overnight',
            'action_type' => 'reserved',
        ])->assertSessionHasNoErrors();

        // Reserve Room 2 via reservation.store
        $this->post(route('reservation.store'), [
            'fname' => 'ReservationTwo',
            'lname' => 'Guest',
            'phone' => '09222222222',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $departure->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room2->id],
            'stay_type' => 'overnight',
        ])->assertSessionHasNoErrors();

        // Dashboard overview checks:
        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();

        // Reserved card displays 2, Occupied card displays 0
        $dashboard->assertViewHas('reservedRooms', 2);
        $dashboard->assertViewHas('occupiedRooms', 0);
        $dashboard->assertSee('id="kpi-reserved">2<', false);
        $dashboard->assertSee('id="kpi-occupied">0<', false);

        // In Room Status Grid: both rooms have status 'reserved' and data-status="reserved"
        $rooms = $dashboard->viewData('rooms');
        $this->assertSame('reserved', $rooms->firstWhere('id', $this->room1->id)->status);
        $this->assertSame('reserved', $rooms->firstWhere('id', $this->room2->id)->status);

        $html = $dashboard->getContent();
        $this->assertMatchesRegularExpression('/data-status="reserved"[\s\S]*?data-room-id="'.$this->room1->id.'"/', $html);
        $this->assertMatchesRegularExpression('/data-status="reserved"[\s\S]*?data-room-id="'.$this->room2->id.'"/', $html);

        // API polling endpoint checks:
        $notif = $this->getJson(route('api.dashboard.notifications'));
        $notif->assertOk();
        $this->assertSame(2, $notif->json('kpi.reserved'));
        $this->assertSame(0, $notif->json('kpi.occupied'));
        $this->assertSame('reserved', $notif->json('room_statuses.'.$this->room1->id));
        $this->assertSame('reserved', $notif->json('room_statuses.'.$this->room2->id));

        // Now check in Room 1's reservation:
        $res1 = Reservation::where('guest_name', 'ReservationOne Guest')->firstOrFail();
        $checkInRes = $this->post(route('reservation.checkIn', $res1->id));
        $checkInRes->assertSessionHasNoErrors();

        // Re-check Dashboard overview:
        $dashboardAfter = $this->get(route('dashboard.index'));
        $dashboardAfter->assertOk();

        // Reserved card decrements to 1, Occupied card increments to 1
        $dashboardAfter->assertViewHas('reservedRooms', 1);
        $dashboardAfter->assertViewHas('occupiedRooms', 1);
        $dashboardAfter->assertSee('id="kpi-reserved">1<', false);
        $dashboardAfter->assertSee('id="kpi-occupied">1<', false);

        // Room 1 is now occupied, Room 2 is still reserved
        $roomsAfter = $dashboardAfter->viewData('rooms');
        $this->assertSame('occupied', $roomsAfter->firstWhere('id', $this->room1->id)->status);
        $this->assertSame('reserved', $roomsAfter->firstWhere('id', $this->room2->id)->status);

        $htmlAfter = $dashboardAfter->getContent();
        $this->assertMatchesRegularExpression('/data-status="occupied"[\s\S]*?data-room-id="'.$this->room1->id.'"/', $htmlAfter);
        $this->assertMatchesRegularExpression('/data-status="reserved"[\s\S]*?data-room-id="'.$this->room2->id.'"/', $htmlAfter);

        // API polling endpoint reflects updated counts
        $notifAfter = $this->getJson(route('api.dashboard.notifications'));
        $notifAfter->assertOk();
        $this->assertSame(1, $notifAfter->json('kpi.reserved'));
        $this->assertSame(1, $notifAfter->json('kpi.occupied'));
        $this->assertSame('occupied', $notifAfter->json('room_statuses.'.$this->room1->id));
        $this->assertSame('reserved', $notifAfter->json('room_statuses.'.$this->room2->id));
    }

    public function test_book_now_modal_has_no_room_mode_dropdown_and_allows_direct_single_and_multi_room_booking(): void
    {
        $dashboard = $this->get(route('dashboard.index'));
        $dashboard->assertOk();

        // The dropdown was removed
        $dashboard->assertDontSee('id="bn_room_mode"', false);
        $dashboard->assertDontSee('One room', false);
        $dashboard->assertDontSee('Multiple rooms', false);

        // Direct single-room booking
        $arrival = Carbon::now()->addHours(1);
        $singleBooking = $this->post(route('booking.store'), [
            'fname' => 'SingleRoom',
            'lname' => 'Guest',
            'phone' => '09123456789',
            'check_in' => $arrival->format('Y-m-d H:i:s'),
            'check_out' => $arrival->copy()->addHours(6)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id],
            'stay_type' => 'short_stay',
            'short_hours' => 6,
            'action_type' => 'check_in',
        ]);
        $singleBooking->assertSessionHasNoErrors();

        // Direct multi-room booking
        $multiArrival = Carbon::now()->addDays(4)->setTime(14, 0);
        $multiBooking = $this->post(route('booking.store'), [
            'fname' => 'MultiRoom',
            'lname' => 'Guest',
            'phone' => '09987654321',
            'check_in' => $multiArrival->format('Y-m-d H:i:s'),
            'check_out' => $multiArrival->copy()->addHours(24)->format('Y-m-d H:i:s'),
            'room_ids' => [$this->room1->id, $this->room2->id],
            'stay_type' => 'overnight',
            'action_type' => 'reserved',
        ]);
        $multiBooking->assertSessionHasNoErrors();
    }
}
