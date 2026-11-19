<?php

namespace App\Http\Controllers\Api;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController
{
    /**
     * Returns guests whose check-out is due within 30 minutes or already overdue,
     * along with current KPI counts and room status mapping for real-time dashboard updates.
     */
    public function notifications()
    {
        $now = now()->setTimezone('Asia/Manila');

        // Collect guests due within the next 30 minutes or already past due
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

            return [
                'name' => trim(($invoice->booking->guest->fname ?? 'Guest').' '.($invoice->booking->guest->lname ?? '')),
                'room_number' => collect($invoice->booking->booked_rooms)
                    ->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))
                    ->implode(', '),
                'checkout_time' => $checkOut->format('h:i A'),
                'is_overdue' => $checkOut->lte($now),
                'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
            ];
        })->values();

        // Automatically mark expired blocks as inactive
        RoomBlock::where('is_active', true)
            ->where('end_date', '<', $now)
            ->update(['is_active' => false]);

        // Calculate accurate room statuses and checkouts matching DashboardController
        $rooms = Room::all();
        $roomStatuses = [];
        $roomCheckouts = [];
        $roomTimes = [];
        $roomBlockingPeriods = [];
        $roomReservationArrivals = [];

        foreach ($rooms as $room) {
            $booking = Booking::where(function ($q) use ($room) {
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
                ->first();

            $block = RoomBlock::where('room_id', $room->id)
                ->where('is_active', true)
                ->where('end_date', '>=', $now)
                ->orderBy('start_date')
                ->first();

            if ($block) {
                $status = 'under_maintenance';
                $startFormatted = $block->start_date->format('n/j/Y, g:i A');
                $endFormatted = $block->end_date->format('n/j/Y, g:i A');
                $roomBlockingPeriods[$room->id] = "Start: {$startFormatted} and End: {$endFormatted}";
            } elseif ($booking) {
                $status = 'occupied';
                $checkIn = Carbon::parse($booking->check_in_datetime);
                $checkOut = Carbon::parse($booking->check_out_datetime);
                $roomCheckouts[$room->id] = $checkOut->toIso8601String();
                $roomTimes[$room->id] = [
                    'in' => $checkIn->format('g:i A'),
                    'out' => $checkOut->format('g:i A'),
                ];
            } else {
                $reservation = Reservation::where(function ($q) use ($room) {
                    $q->whereJsonContains('selected_rooms', (string) $room->id)
                        ->orWhereJsonContains('selected_rooms', $room->id);
                })
                    ->where('status', 'reserved')
                    ->where('reservation_end_date', '>=', $now)
                    ->orderBy('reservation_start_date')
                    ->first();

                $reservedBooking = Booking::where(function ($q) use ($room) {
                    $q->whereJsonContains('booked_rooms', (string) $room->id)
                        ->orWhereJsonContains('booked_rooms', $room->id);
                })
                    ->where('status', 'reserved')
                    ->where('check_out_datetime', '>=', $now)
                    ->orderBy('check_in_datetime')
                    ->first();

                if ($reservation || $reservedBooking || $room->status === 'reserved') {
                    $status = 'reserved';
                    if ($reservation) {
                        $arrivalStart = Carbon::parse($reservation->reservation_start_date);
                        $roomReservationArrivals[$room->id] = $arrivalStart->format('M j, Y g:i A');
                    } elseif ($reservedBooking) {
                        $arrivalStart = Carbon::parse($reservedBooking->check_in_datetime);
                        $roomReservationArrivals[$room->id] = $arrivalStart->format('M j, Y g:i A');
                    }
                } else {
                    $status = 'available';
                }
            }

            $roomStatuses[$room->id] = $status;
        }

        // Current KPI counts for the Overview cards
        $occupiedCount = collect($roomStatuses)->filter(fn ($s) => $s === 'occupied')->count();
        $availableCount = collect($roomStatuses)->filter(fn ($s) => $s === 'available')->count();
        $reservedCount = collect($roomStatuses)->filter(fn ($s) => $s === 'reserved')->count();
        $maintenanceCount = collect($roomStatuses)->filter(fn ($s) => $s === 'under_maintenance')->count();

        return response()->json([
            'due_guests' => $dueGuests,
            'kpi' => [
                'available' => $availableCount,
                'occupied' => $occupiedCount,
                'reserved' => $reservedCount,
                'maintenance' => $maintenanceCount,
            ],
            'room_statuses' => $roomStatuses,
            'room_checkouts' => $roomCheckouts,
            'room_times' => $roomTimes,
            'room_blocking_periods' => $roomBlockingPeriods,
            'room_reservation_arrivals' => $roomReservationArrivals,
            'amenities' => Amenity::where('offered', true)->orderBy('name', 'asc')->get()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'item_name' => $a->name,
                'price' => (float) $a->price,
                'price_formatted' => number_format((float) $a->price, 2),
                'charged' => $a->charged ?? 'item',
                'offered' => (bool) $a->offered,
                'is_available' => (bool) $a->offered,
            ]),
            'server_time' => $now->toIso8601String(),
        ]);
    }

    /**
     * Real-time amenities endpoint for Receptionist dropdown and Available Amenities list synchronization.
     */
    public function amenities(Request $request)
    {
        $query = Amenity::query()->orderBy('name', 'asc');

        if (! $request->boolean('all')) {
            $query->where('offered', true);
        }

        $amenities = $query->get()->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'item_name' => $a->name,
            'price' => (float) $a->price,
            'price_formatted' => number_format((float) $a->price, 2),
            'charged' => $a->charged ?? 'item',
            'offered' => (bool) $a->offered,
            'is_available' => (bool) $a->offered,
        ]);

        return response()->json([
            'status' => 'success',
            'amenities' => $amenities,
            'count' => $amenities->count(),
            'server_time' => now()->setTimezone('Asia/Manila')->toIso8601String(),
        ]);
    }

    /**
     * Real-time overview statistics for the Administrator dashboard (current day only, Asia/Manila).
     */
    public function adminOverviewStats()
    {
        $today = now()->setTimezone('Asia/Manila')->toDateString();

        // 1. Today's Occupancy: count of distinct rooms occupied or booked today
        $todayBookings = Booking::where('status', '!=', 'cancelled')
            ->where(function ($q) use ($today) {
                $q->where('status', 'checked_in')
                    ->orWhereDate('check_in_datetime', $today)
                    ->orWhereDate('created_at', $today);
            })
            ->get();

        $occupiedRoomIds = collect();
        foreach ($todayBookings as $booking) {
            $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : json_decode($booking->booked_rooms, true);
            if (! empty($rooms)) {
                foreach ($rooms as $rId) {
                    $occupiedRoomIds->push((int) $rId);
                }
            }
        }
        $occupancyToday = $occupiedRoomIds->unique()->count();
        $totalRooms = Room::count();

        // 2. Revenue Today: total money actually collected from guests today
        $revenueToday = (float) PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($today) {
                $q->whereDate('paid_at', $today)
                    ->orWhereDate('created_at', $today);
            })
            ->sum('amount_paid');

        // 3. Check-ins Today: all guests who actually checked in today (excluding reservations still marked 'reserved')
        $checkInsList = Booking::with('guest')
            ->where(function ($q) use ($today) {
                $q->whereDate('check_in_datetime', $today)
                    ->orWhereDate('created_at', $today);
            })
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->orderByDesc('check_in_datetime')
            ->get();

        $checkInsToday = $checkInsList->count();

        // 4. Check-outs Today: total guests who have checked out of their occupied rooms today
        $checkOutsList = Booking::with('guest')
            ->where('status', 'checked_out')
            ->where(function ($q) use ($today) {
                $q->whereDate('actual_check_out', $today)
                    ->orWhere(function ($sub) use ($today) {
                        $sub->whereNull('actual_check_out')
                            ->where(function ($dt) use ($today) {
                                $dt->whereDate('check_out_datetime', $today)
                                    ->orWhereDate('updated_at', $today);
                            });
                    });
            })
            ->orderByDesc('actual_check_out')
            ->get();

        $checkOutsToday = $checkOutsList->count();

        $checkInsFormatted = $checkInsList->map(function ($b) {
            $name = trim(($b->guest->fname ?? 'Guest').' '.($b->guest->lname ?? ''));
            $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
            $roomStr = ! empty($rooms) ? 'Room '.implode(', ', $rooms) : 'Room N/A';
            $timeStr = $b->check_in_datetime ? Carbon::parse($b->check_in_datetime)->format('g:i A') : '';

            return [
                'name' => $name,
                'data' => $timeStr ? "{$roomStr} • {$timeStr}" : $roomStr,
            ];
        });

        $checkOutsFormatted = $checkOutsList->map(function ($b) {
            $name = trim(($b->guest->fname ?? 'Guest').' '.($b->guest->lname ?? ''));
            $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
            $roomStr = ! empty($rooms) ? 'Room '.implode(', ', $rooms) : 'Room N/A';
            $time = $b->actual_check_out ?? $b->check_out_datetime;
            $timeStr = $time ? Carbon::parse($time)->format('g:i A') : '';

            return [
                'name' => $name,
                'data' => $timeStr ? "{$roomStr} • {$timeStr}" : $roomStr,
            ];
        });

        return response()->json([
            'occupancy_today' => $occupancyToday,
            'total_rooms' => $totalRooms,
            'revenue_today' => $revenueToday,
            'revenue_today_formatted' => '₱'.number_format($revenueToday, 2),
            'checkins_today' => $checkInsToday,
            'checkouts_today' => $checkOutsToday,
            'checkins_list' => $checkInsFormatted,
            'checkouts_list' => $checkOutsFormatted,
        ]);
    }
}
