<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;

class ReservationController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = Reservation::with('guest')->latest()->get();

        return view('content.reservation', compact('reservations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fname' => 'nullable|string|min:2|max:30',
            'lname' => 'nullable|string|min:2|max:30',
            'phone' => 'nullable|string|max:15',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'room_ids' => 'required|array',
            'room_ids.*' => 'integer|exists:rooms,id',
        ]);

        // Minimum 24-hour stay check using PHP native dates (no Carbon)
        $checkIn = new \DateTime($request->check_in);
        $checkOut = new \DateTime($request->check_out);
        $diffHours = (int) $checkIn->diff($checkOut)->h + ((int) $checkIn->diff($checkOut)->days * 24);

        if ($diffHours < 24) {
            return back()->withErrors(['check_out' => 'Minimum 24 hours stay is required for an overnight stay.']);
        }

        $fname = trim($request->fname) ?: 'Anonymous';
        $lname = trim($request->lname) ?: 'Guest';

        // Create a Guest record from the form data.
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => $request->phone,
        ]);

        Reservation::create([
            'guest_name' => $fname.' '.$lname,
            'guest_id' => $guest->id,
            'phone_number' => $request->phone ?: 'None',
            'reservation_start_date' => $request->check_in,
            'reservation_end_date' => $request->check_out,
            'number_of_rooms' => count($request->room_ids),
            // Laravel's $casts converts this array to JSON automatically
            'selected_rooms' => $request->room_ids,
        ]);

        return redirect()->back()->with('success', 'Reservation created successfully.');
    }

    /**
     * Update the guest info linked to a reservation.
     */
    public function update(Request $request, int $id)
    {
        $reservation = Reservation::with('guest')->findOrFail($id);

        $request->validate([
            'fname' => 'nullable|string|min:2|max:30',
            'lname' => 'nullable|string|min:2|max:30',
            'phone' => 'nullable|string|max:15',
        ]);

        $fname = trim($request->fname) ?: $reservation->guest->fname;
        $lname = trim($request->lname) ?: $reservation->guest->lname;

        $reservation->guest->update([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => $request->phone,
        ]);

        $reservation->update([
            'guest_name' => $fname.' '.$lname,
            'phone_number' => $request->phone ?: 'None',
        ]);

        return redirect()->route('reservation.index')->with('success', 'Reservation updated successfully.');
    }

    /**
     * Delete a reservation.
     */
    public function destroy(int $id)
    {
        Reservation::findOrFail($id)->delete();

        return redirect()->route('reservation.index')->with('success', 'Reservation deleted successfully.');
    }

    public function getAvailableRooms(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if (! $start || ! $end) {
            return response()->json([]);
        }

        $overlappingReservations = Reservation::where(function ($query) use ($start, $end) {
            $query->whereBetween('reservation_start_date', [$start, $end])
                ->orWhereBetween('reservation_end_date', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('reservation_start_date', '<=', $start)
                        ->where('reservation_end_date', '>=', $end);
                });
        })->get();

        $overlappingBookings = Booking::where(function ($query) use ($start, $end) {
            $query->whereBetween('check_in_datetime', [$start, $end])
                ->orWhereBetween('check_out_datetime', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('check_in_datetime', '<=', $start)
                        ->where('check_out_datetime', '>=', $end);
                });
        })->get();

        $bookedRoomIds = [];
        foreach ($overlappingReservations as $res) {
            $bookedRoomIds = array_merge($bookedRoomIds, $res->selected_rooms ?? []);
        }
        foreach ($overlappingBookings as $booking) {
            $bookedRoomIds = array_merge($bookedRoomIds, $booking->booked_rooms ?? []);
        }

        $bookedRoomIds = array_unique($bookedRoomIds);
        $availableRooms = Room::whereNotIn('id', $bookedRoomIds)->get();

        return response()->json($availableRooms);
    }
}
