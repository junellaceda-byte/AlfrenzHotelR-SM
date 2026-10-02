<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;

class DashboardController
{
    //
    public function index()
    {
        // Retrieves all rooms
        $rooms = Room::all();

        // Retrieves all rooms with the status of available
        $availableRooms = Room::where('status', 'available')->count();

        // Retrieves all rooms with the status of occupied
        $occupiedRooms = Room::where('status', 'occupied')->count();

        // Retrieves all active bookings
        $activeBookings = Booking::count();
        $underMaintenanceRooms = Room::where('status', 'under_maintenance')->count();
        $reservedRooms = Room::where('status', 'reserved')->count();

        return view('content.receptionist', compact('rooms', 'availableRooms', 'occupiedRooms', 'activeBookings', 'underMaintenanceRooms', 'reservedRooms'));
    }
}
