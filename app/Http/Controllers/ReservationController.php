<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Services\BookingService;

class ReservationController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Status filter: arriving today, cancelled, or all
        $filter = $request->query('filter', 'today');
        $today = now()->setTimezone('Asia/Manila')->toDateString();

        // The list is sorted A to Z by the guest's first name
        $query = Reservation::with('guest')
            ->orderBy('guest_name')
            ->orderBy('reservation_start_date');

        if ($filter === 'today') {
            $query->where('status', 'reserved')->whereDate('reservation_start_date', $today);
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $reservations = $query->paginate(30)->withQueryString();

        return view('content.reservation', compact('reservations', 'filter'));
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

        // Parse the check-in date and time from the request
        $checkIn = \Carbon\Carbon::parse($request->check_in);
        // Check if the check-in time is in the past (more than 5 minutes ago) and return an error if so
        if ($checkIn->lt(now()->subMinutes(5))) {
            return back()->withErrors(['check_in' => 'The arrival date and time cannot be in the past.'])->withInput();

        }
        // Calculate the check-out time based on the check-in time and the stay type (short stay or overnight)
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

    // This function retrieves available rooms based on the specified date range in the request. It checks for overlapping reservations and bookings, excluding those that are cancelled or checked in/out, and returns a list of available rooms.
    public function getAvailableRooms(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if (! $start || ! $end) {
            return response()->json([]);
        }
        // Query for reservations and bookings that overlap with the specified date range, excluding those that are cancelled or checked in/out
        $overlappingReservations = Reservation::whereNotIn('status', ['cancelled', 'checked_in'])->where(function ($query) use ($start, $end) {
            $query->whereBetween('reservation_start_date', [$start, $end])
                ->orWhereBetween('reservation_end_date', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('reservation_start_date', '<=', $start)
                        ->where('reservation_end_date', '>=', $end);
                });
        })->get();
        // Query for bookings that overlap with the specified date range, excluding those that are cancelled or checked out
        $overlappingBookings = Booking::whereNotIn('status', ['cancelled', 'checked_out'])->where(function ($query) use ($start, $end) {
            $query->whereBetween('check_in_datetime', [$start, $end])
                ->orWhereBetween('check_out_datetime', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('check_in_datetime', '<=', $start)
                        ->where('check_out_datetime', '>=', $end);
                });
        })->get();
        // Collect the IDs of rooms that are booked in overlapping reservations and bookings
        $bookedRoomIds = [];
        foreach ($overlappingReservations as $res) {
            $bookedRoomIds = array_merge($bookedRoomIds, $res->selected_rooms ?? []);
        }
        foreach ($overlappingBookings as $booking) {
            $bookedRoomIds = array_merge($bookedRoomIds, $booking->booked_rooms ?? []);
        }
        // Remove duplicate room IDs and retrieve the available rooms that are not in the list of booked room IDs
        $bookedRoomIds = array_unique($bookedRoomIds);
        $availableRooms = Room::whereNotIn('id', $bookedRoomIds)->get();

        return response()->json($availableRooms);
    }
    // Added functions
    public function checkIn(Request $request, int $id, BookingService $bookings)
    {
        // Validate the discount type if provided
        $request->validate(['discount_type' => 'nullable|in:senior,pwd']);

        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'reserved') {
            return back()->withErrors(['reservation' => 'Only a reserved reservation can be checked in.']);
        }
        // Create a booking using the BookingService and update the reservation status to 'checked_in'
        [$booking, $price] = $bookings->create([
            'guest_id' => $reservation->guest_id,
            'phone' => $reservation->phone_number,
            'check_in' => now()->setTimezone('Asia/Manila'),
            'stay_type' => $reservation->stay_type,
            'short_hours' => $reservation->short_stay_hours,
            'with_breakfast' => $reservation->with_breakfast,
            'extra_beds' => 0,
            'discount_type' => $request->discount_type,
            'room_ids' => $reservation->selected_rooms,
            'guests' => $reservation->guests ?? 1,
            'status' => 'checked_in',
            'deposit' => $reservation->deposit ?? 0,
        ]);

        $reservation->update(['status' => 'checked_in']);

        return redirect()->route('reservation.index')
            ->with('success', 'Guest checked in. Total: ₱' . number_format($price['total'], 2));
    }

    /** Keeps the row (status Cancelled) and frees the rooms */
    public function cancel(int $id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status === 'reserved') {
            $reservation->update(['status' => 'cancelled']);
        }

        return redirect()->route('reservation.index', ['filter' => 'cancelled'])
            ->with('success', 'Reservation cancelled.');
    }
}
