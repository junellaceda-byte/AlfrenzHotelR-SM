<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;

class RoomAvailabilityService
{
    /**
     * Returns the room IDs (from the given $candidateIds) that are unavailable
     * for the requested stay window [$start, $end).
     *
     * Checks:
     *  1. Active checked-in bookings whose occupancy overlaps the window.
     *  2. Active reservations with overlapping datetimes.
     *  3. Active bookings (not cancelled/checked_out) with overlapping datetimes.
     *  4. Active maintenance blocks covering the window.
     *
     * @param  Carbon  $start  Requested check-in datetime.
     * @param  Carbon  $end  Requested check-out datetime.
     * @param  array<int>  $candidateIds  The room IDs to test; pass all IDs to get a full exclusion list.
     * @param  int|null  $excludeBookingId  Booking ID to ignore (useful when editing).
     * @param  int|null  $excludeReservationId  Reservation ID to ignore (useful when editing).
     * @return array<int> Room IDs (subset of $candidateIds) that are unavailable.
     */
    public function conflictingRoomIds(
        Carbon $start,
        Carbon $end,
        array $candidateIds = [],
        ?int $excludeBookingId = null,
        ?int $excludeReservationId = null
    ): array {
        $startStr = $start->toDateTimeString();
        $endStr = $end->toDateTimeString();
        $now = now()->setTimezone('Asia/Manila');

        $bookedRoomIds = [];

        // 1. Currently checked-in bookings (status = checked_in) that overlap.
        $checkedInQuery = Booking::where('status', 'checked_in');
        if ($excludeBookingId) {
            $checkedInQuery->where('id', '!=', $excludeBookingId);
        }
        foreach ($checkedInQuery->get() as $b) {
            $bStart = Carbon::parse($b->check_in_datetime);
            $bEnd = Carbon::parse($b->check_out_datetime);
            // Room stays occupied until check-out (or now if already past).
            $effectiveEnd = $bEnd->gt($now) ? $bEnd : $now->copy()->addMinute();
            if ($start->lt($effectiveEnd) && $end->gt($bStart)) {
                $bookedRoomIds = array_merge($bookedRoomIds, $b->booked_rooms ?? []);
            }
        }

        // 2. Active reservations (status = reserved) with overlapping window.
        $resQuery = Reservation::where('status', 'reserved')
            ->where('reservation_start_date', '<', $endStr)
            ->where('reservation_end_date', '>', $startStr);
        if ($excludeReservationId) {
            $resQuery->where('id', '!=', $excludeReservationId);
        }
        foreach ($resQuery->get() as $res) {
            $bookedRoomIds = array_merge($bookedRoomIds, $res->selected_rooms ?? []);
        }

        // 3. Other active bookings (not cancelled/checked_out/checked_in) with overlapping window.
        $bookingQuery = Booking::whereNotIn('status', ['cancelled', 'checked_out', 'checked_in'])
            ->where('check_in_datetime', '<', $endStr)
            ->where('check_out_datetime', '>', $startStr);
        if ($excludeBookingId) {
            $bookingQuery->where('id', '!=', $excludeBookingId);
        }
        foreach ($bookingQuery->get() as $bk) {
            $bookedRoomIds = array_merge($bookedRoomIds, $bk->booked_rooms ?? []);
        }

        // 4. Active maintenance blocks that overlap.
        $blockedIds = RoomBlock::where('is_active', true)
            ->where('start_date', '<', $endStr)
            ->where('end_date', '>', $startStr)
            ->pluck('room_id')
            ->all();
        $bookedRoomIds = array_merge($bookedRoomIds, $blockedIds);

        $unavailable = array_values(array_unique(array_map('intval', array_filter($bookedRoomIds))));

        // If caller provided a candidate list, return only the intersection.
        if (! empty($candidateIds)) {
            $candidateInts = array_map('intval', $candidateIds);
            $unavailable = array_values(array_intersect($unavailable, $candidateInts));
        }

        return $unavailable;
    }

    /**
     * Returns true when ALL rooms in $roomIds are free for [$start, $end).
     */
    public function allRoomsFree(
        Carbon $start,
        Carbon $end,
        array $roomIds,
        ?int $excludeBookingId = null,
        ?int $excludeReservationId = null
    ): bool {
        return empty(
            $this->conflictingRoomIds($start, $end, $roomIds, $excludeBookingId, $excludeReservationId)
        );
    }
}
