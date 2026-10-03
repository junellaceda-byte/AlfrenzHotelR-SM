<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;

class DashboardController
{
    //
    public function index()
    {
        $now = now()->setTimezone('Asia/Manila');

        // Retrieves all rooms with active time ranges
        $rooms = Room::all()->map(function($room) use ($now) {
            $timeRange = '00:00-23:59';
            
            $booking = Booking::whereJsonContains('booked_rooms', (string)$room->id)
                ->orWhereJsonContains('booked_rooms', $room->id)
                ->where('check_in_datetime', '<=', $now)
                ->where('check_out_datetime', '>=', $now)
                ->first();
                
            if ($booking) {
                $timeRange = \Carbon\Carbon::parse($booking->check_in_datetime)->format('H:i') . '-' . \Carbon\Carbon::parse($booking->check_out_datetime)->format('H:i');
            } else {
                $reservation = \App\Models\Reservation::whereJsonContains('selected_rooms', (string)$room->id)
                    ->orWhereJsonContains('selected_rooms', $room->id)
                    ->where('reservation_start_date', '<=', $now)
                    ->where('reservation_end_date', '>=', $now)
                    ->first();
                    
                if ($reservation) {
                    $timeRange = \Carbon\Carbon::parse($reservation->reservation_start_date)->format('H:i') . '-' . \Carbon\Carbon::parse($reservation->reservation_end_date)->format('H:i');
                }
            }
            
            $room->timeRange = $timeRange;
            return $room;
        });

        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $activeBookings = Booking::count();
        $underMaintenanceRooms = Room::where('status', 'under_maintenance')->count();
        $reservedRooms = Room::where('status', 'reserved')->count();

        // Occupancy Trends
        $today = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();

        $weeklyData = [];
        $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = \App\Models\Invoice::where('status', 'paid')
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
        $paidToday = \App\Models\Invoice::where('status', 'paid')
            ->whereHas('booking', function ($q) use ($today) {
                $q->whereDate('check_in_datetime', '<=', $today->toDateString())
                  ->whereDate('check_out_datetime', '>=', $today->toDateString());
            })->with('booking')->get();

        foreach ($paidToday as $invoice) {
            $roomCount = is_array($invoice->booking->booked_rooms) ? count($invoice->booking->booked_rooms) : 1;
            $checkIn = \Carbon\Carbon::parse($invoice->booking->check_in_datetime);
            $checkOut = \Carbon\Carbon::parse($invoice->booking->check_out_datetime);
            
            if ($checkIn->isBefore($today->copy()->setTime(12, 0)) && $checkOut->isAfter($today->copy()->setTime(6, 0))) $todayData[0] += $roomCount;
            if ($checkIn->isBefore($today->copy()->setTime(18, 0)) && $checkOut->isAfter($today->copy()->setTime(12, 0))) $todayData[1] += $roomCount;
            if ($checkIn->isBefore($today->copy()->setTime(23, 59)) && $checkOut->isAfter($today->copy()->setTime(18, 0))) $todayData[2] += $roomCount;
            if ($checkIn->isBefore($today->copy()->setTime(6, 0)->addDay()) && $checkOut->isAfter($today->copy()->setTime(0, 0))) $todayData[3] += $roomCount;
        }

        $chartData = [
            'today' => ['labels' => $todayLabels, 'data' => $todayData],
            'weekly' => ['labels' => $weeklyLabels, 'data' => $weeklyData],
        ];

        return view('content.receptionist', compact('rooms', 'availableRooms', 'occupiedRooms', 'activeBookings', 'underMaintenanceRooms', 'reservedRooms', 'chartData'));
    }
}
