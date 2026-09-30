<?php

namespace App\Http\Controllers;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController 
{
    //
    public function index()
    {
        $rooms = Room::with(['bookings' => function ($query) {
            $query->where('status', 'checked_in');
        }])->get();

        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $activeBookings = Booking::where('status', 'checked_in')->count();
        $underMaintenanceRooms = Room::where('status', 'under_maintenance')->count();
        $reservedRooms = Room::where('status', 'reserved')->count();

        return view('content.receptionist', compact('rooms', 'availableRooms', 'occupiedRooms', 'activeBookings', 'underMaintenanceRooms', 'reservedRooms'));
    }
}
