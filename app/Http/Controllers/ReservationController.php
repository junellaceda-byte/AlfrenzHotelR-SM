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
    public function index(Request $request)
    {
        $query = Reservation::with('guest')->latest();
        
        if ($request->filled('search')) {
            $query->where('guest_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->whereDate('reservation_start_date', '>=', $request->start_date)
                  ->whereDate('reservation_start_date', '<=', $request->end_date);
            });
        }

        $reservations = $query->paginate(30);

        return view('content.reservation', compact('reservations'));
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'fname' => 'required|string|max:30',
            'lname' => 'nullable|string|max:30',
            'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:15',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'integer|exists:rooms,id',
            'stay_type' => 'required|in:short_stay,overnight',
            'short_hours' => 'required_if:stay_type,short_stay|nullable|in:3,6,12',
            'with_breakfast' => 'nullable|boolean',
            'extra_beds' => 'nullable|integer|min:0|max:5',
            'deposit' => 'nullable|numeric|min:0',
            'guests' => 'nullable|integer|min:1|max:20',
        ]);
        // Determine if the stay type is short stay or overnight
        $isShort = $request->stay_type === 'short_stay';
        $fname = trim($request->fname);
        $lname = trim($request->lname ?? '');

        // Check-out is always worked out here: arrival + chosen hours (or 24 hours)
        $checkIn = \Carbon\Carbon::parse($request->check_in);
        $checkOut = $checkIn->copy()->addHours($isShort ? (int) $request->short_hours : 24);
        // Create a new guest record
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => $request->phone,
        ]);
        // Create a new reservation record
        Reservation::create([
            'guest_name' => trim($fname . ' ' . $lname),
            'guest_id' => $guest->id,
            'phone_number' => $request->phone,
            'reservation_start_date' => $checkIn,
            'reservation_end_date' => $checkOut,
            'number_of_rooms' => count($request->room_ids),
            'selected_rooms' => $request->room_ids,
            'guests' => $request->guests ?? 1,
            'status' => 'reserved',
            'stay_type' => $request->stay_type,
            'short_stay_hours' => $isShort ? (int) $request->short_hours : null,
            'with_breakfast' => $request->boolean('with_breakfast'),
            'extra_beds' => $isShort ? 0 : (int) $request->input('extra_beds', 0),
            'deposit' => $request->deposit ?? 0,
        ]);
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Reservation created successfully.');
        //End of store method
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
