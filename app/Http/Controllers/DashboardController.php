<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Services\SystemFormatService;
use Carbon\Carbon;

class DashboardController
{
    /**
     * Compute real-time room statuses, KPI counts, and active maintenance blocks.
     * Shared by both Receptionist Dashboard and Administrator Room Management.
     */
    public static function getRoomsData(): array
    {
        $now = now()->setTimezone('Asia/Manila');

        // Automatically mark expired blocks as inactive
        RoomBlock::where('is_active', true)
            ->where('end_date', '<', $now)
            ->update(['is_active' => false]);

        // Retrieves all rooms with active time ranges
        // and determines their status based on bookings and reservations
        $rooms = Room::all()->map(function ($room) use ($now) {
            $timeRange = '00:00-23:59';

            // A stay happening right now (or checked in guest who has not checked out)
            $booking = Booking::with('guest')->where(function ($q) use ($room) {
                $q->whereJsonContains('booked_rooms', (string) $room->id)
                    ->orWhereJsonContains('booked_rooms', $room->id);
            })
                ->where('status', 'checked_in')
                ->where('check_in_datetime', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where('check_out_datetime', '>=', $now)
                        ->orWhere('status', 'checked_in');
                })
                ->orderByDesc('check_in_datetime')
                ->orderByDesc('id')
                ->first();

            // An active reservation for this room that has not been checked in yet
            $reservation = null;
            $reservedBooking = null;
            if (! $booking) {
                $reservation = Reservation::where(function ($q) use ($room) {
                    $q->whereJsonContains('selected_rooms', (string) $room->id)
                        ->orWhereJsonContains('selected_rooms', $room->id);
                })
                    ->where('status', 'reserved')
                    ->where('reservation_end_date', '>=', $now)
                    ->orderBy('reservation_start_date')
                    ->first();

                if (! $reservation) {
                    $reservedBooking = Booking::with('guest')->where(function ($q) use ($room) {
                        $q->whereJsonContains('booked_rooms', (string) $room->id)
                            ->orWhereJsonContains('booked_rooms', $room->id);
                    })
                        ->where('status', 'reserved')
                        ->where('check_out_datetime', '>=', $now)
                        ->orderBy('check_in_datetime')
                        ->first();
                }
            }

            // An active maintenance block that has not ended yet wins over everything else
            $block = RoomBlock::where('room_id', $room->id)
                ->where('is_active', true)
                ->where('end_date', '>=', $now)
                ->orderBy('start_date')
                ->first();

            $isOverdue = false;
            $overdueFormatted = null;
            $checkoutDatetime = null;
            $blockingPeriod = null;

            if ($block) {
                $room->status = 'under_maintenance';
                $startFormatted = SystemFormatService::formatDateTime($block->start_date);
                $endFormatted = SystemFormatService::formatDateTime($block->end_date);
                $blockingPeriod = "Start: {$startFormatted} and End: {$endFormatted}";
                $timeRange = SystemFormatService::formatTime($block->start_date).'-'.SystemFormatService::formatTime($block->end_date);
            } elseif ($booking) {
                $room->status = 'occupied';
                $room->guest_name = $booking->guest ? trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')) : 'Guest';
                $room->booking_id = $booking->id;
                $checkIn = Carbon::parse($booking->check_in_datetime);
                $checkOut = Carbon::parse($booking->check_out_datetime);
                $timeRange = SystemFormatService::formatTime($checkIn).'-'.SystemFormatService::formatTime($checkOut);
                $checkoutDatetime = $checkOut->toIso8601String();

                if ($checkOut->lt($now)) {
                    $isOverdue = true;
                    $diff = (int) abs($now->diffInSeconds($checkOut, false));
                    $days = intdiv($diff, 86400);
                    $hours = intdiv($diff % 86400, 3600);
                    $minutes = intdiv($diff % 3600, 60);
                    $seconds = $diff % 60;

                    if ($days > 0) {
                        $overdueFormatted = sprintf('%dd %02d:%02d:%02d', $days, $hours, $minutes, $seconds);
                    } else {
                        $overdueFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    }
                }
            } elseif ($reservation || $reservedBooking || $room->status === 'reserved') {
                $room->status = 'reserved';
                $timeRange = null;
                if ($reservation) {
                    $arrivalStart = Carbon::parse($reservation->reservation_start_date);
                    $room->reservation_arrival = SystemFormatService::formatDateTime($arrivalStart);
                } elseif ($reservedBooking) {
                    $arrivalStart = Carbon::parse($reservedBooking->check_in_datetime);
                    $room->reservation_arrival = SystemFormatService::formatDateTime($arrivalStart);
                }
            } else {
                $room->status = 'available';
            }
            // Add the time range and blocking period to the room object for display purposes
            $room->timeRange = $timeRange;
            $room->blocking_period = $blockingPeriod;
            $room->is_overdue = $isOverdue;
            $room->overdue_formatted = $overdueFormatted;
            $room->checkout_datetime = $checkoutDatetime;

            return $room;
        });

        // Count the number of rooms in each status category
        $availableRooms = $rooms->where('status', 'available')->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $reservedRooms = $rooms->where('status', 'reserved')->count();
        $underMaintenanceRooms = $rooms->where('status', 'under_maintenance')->count();

        // Current and future maintenance blocks, for the Block Room window
        $activeBlocks = RoomBlock::with('room')
            ->where('is_active', true)
            ->where('end_date', '>=', $now)
            ->orderBy('start_date')
            ->get();

        return compact('rooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'underMaintenanceRooms', 'activeBlocks');
    }

    public function index()
    {
        $now = now()->setTimezone('Asia/Manila');

        $roomData = self::getRoomsData();
        $rooms = $roomData['rooms'];
        $availableRooms = $roomData['availableRooms'];
        $occupiedRooms = $roomData['occupiedRooms'];
        $reservedRooms = $roomData['reservedRooms'];
        $underMaintenanceRooms = $roomData['underMaintenanceRooms'];
        $activeBlocks = $roomData['activeBlocks'];
        // Count the number of active bookings
        $activeBookings = Booking::count();
        $shortRate = Room::value('short_stay_rate');
        $overnightRate = Room::value('overnight_rate');

        // Occupancy Trends
        $today = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();

        $weeklyData = [];
        $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = Invoice::where('status', 'paid')
                ->whereHas('booking', function ($q) use ($date) {
                    $q->whereDate('check_in_datetime', '<=', $date->toDateString())
                        ->whereDate('check_out_datetime', '>=', $date->toDateString());
                })->with('booking')->get()
                ->pluck('booking.booked_rooms')
                ->flatten()
                ->count();
            $weeklyData[] = $count;
        }

        $todayData = [0, 0, 0, 0];
        $todayLabels = ['Morning', 'Afternoon', 'Evening', 'Night'];
        $paidToday = Invoice::where('status', 'paid')
            ->whereHas('booking', function ($q) use ($today) {
                $q->whereDate('check_in_datetime', '<=', $today->toDateString())
                    ->whereDate('check_out_datetime', '>=', $today->toDateString());
            })->with('booking')->get();

        foreach ($paidToday as $invoice) {
            $roomCount = is_array($invoice->booking->booked_rooms) ? count($invoice->booking->booked_rooms) : 1;
            $checkIn = Carbon::parse($invoice->booking->check_in_datetime);
            $checkOut = Carbon::parse($invoice->booking->check_out_datetime);

            if ($checkIn->isBefore($today->copy()->setTime(12, 0)) && $checkOut->isAfter($today->copy()->setTime(6, 0))) {
                $todayData[0] += $roomCount;
            }
            if ($checkIn->isBefore($today->copy()->setTime(18, 0)) && $checkOut->isAfter($today->copy()->setTime(12, 0))) {
                $todayData[1] += $roomCount;
            }
            if ($checkIn->isBefore($today->copy()->setTime(23, 59)) && $checkOut->isAfter($today->copy()->setTime(18, 0))) {
                $todayData[2] += $roomCount;
            }
            if ($checkIn->isBefore($today->copy()->setTime(6, 0)->addDay()) && $checkOut->isAfter($today->copy()->setTime(0, 0))) {
                $todayData[3] += $roomCount;
            }
        }

        $chartData = [
            'today' => ['labels' => $todayLabels, 'data' => $todayData],
            'weekly' => ['labels' => $weeklyLabels, 'data' => $weeklyData],
        ];

        // Build $dueGuests: guests whose check-out is within the next 30 minutes or already overdue
        $dueInvoices = Invoice::with('booking.guest')
            ->where('status', 'unpaid')
            ->whereHas('booking', function ($q) use ($now) {
                $q->where('status', 'checked_in')
                    ->where('check_out_datetime', '<=', $now->copy()->addMinutes(30));
            })
            ->get()
            ->sortBy(fn ($invoice) => $invoice->booking->check_out_datetime);

        $dueGuests = $dueInvoices->map(function ($invoice) use ($now) {
            $checkOut = Carbon::parse($invoice->booking->check_out_datetime);

            return (object) [
                'name' => trim(($invoice->booking->guest->fname ?? 'Guest').' '.($invoice->booking->guest->lname ?? '')),
                'room_number' => collect($invoice->booking->booked_rooms)
                    ->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))
                    ->implode(', '),
                'checkout_time' => SystemFormatService::formatTime($checkOut),
                'is_overdue' => $checkOut->lte($now),
                'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
            ];
        })->values();

        // Build $occupiedRoomsList: rooms currently occupied (for the Add Amenities modal in topbar)
        $occupiedRoomsList = $rooms->where('status', 'occupied')->values();
        $availableAmenities = Amenity::where('offered', true)->get();

        return view('Receptionist.content.receptionist', compact('rooms', 'availableRooms', 'occupiedRooms', 'activeBookings', 'underMaintenanceRooms', 'reservedRooms', 'chartData', 'shortRate', 'overnightRate', 'activeBlocks', 'dueGuests', 'occupiedRoomsList', 'availableAmenities'));
        // End of the index method
    }
}
