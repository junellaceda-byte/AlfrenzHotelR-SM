<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Services\ActivityLogService;
use App\Services\BookingService;
use App\Services\RoomAvailabilityService;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Status filter: all (default), arriving today, or cancelled
        $filter = $request->query('filter', 'all');
        $today = now()->setTimezone('Asia/Manila')->toDateString();

        // Reserved records first, then all others newest-first
        $query = Reservation::with('guest')
            ->orderByRaw("CASE WHEN status = 'reserved' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($filter === 'today') {
            $query->where('status', 'reserved')->whereDate('reservation_start_date', $today);
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $reservations = $query->paginate(8)->withQueryString();

        // Build $dueGuests and $occupiedRoomsList for the shared topbar partial
        $now = now()->setTimezone('Asia/Manila');
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

        $occupiedRoomsList = Room::all()->filter(function ($room) {
            $booking = Booking::with('guest')->where(function ($q) use ($room) {
                $q->whereJsonContains('booked_rooms', (string) $room->id)
                    ->orWhereJsonContains('booked_rooms', $room->id);
            })->where('status', 'checked_in')->latest('check_in_datetime')->first();

            if ($booking) {
                $room->guest_name = $booking->guest ? trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')) : 'Guest';
                $room->booking_id = $booking->id;

                return true;
            }

            return false;
        })->values();

        $availableAmenities = Amenity::where('offered', true)->get();

        return view('Receptionist.content.reservation', compact('reservations', 'filter', 'dueGuests', 'occupiedRoomsList', 'availableAmenities'));
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(Request $request, RoomAvailabilityService $availability)
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
            'payment_method' => 'nullable|in:cash,ewallet',
            'ewallet_type' => 'nullable|required_if:payment_method,ewallet|string|max:50',
            'reference_number' => 'nullable|required_if:payment_method,ewallet|string|max:100',
        ]);
        // Determine if the stay type is short stay or overnight
        $isShort = $request->stay_type === 'short_stay';
        $fname = trim($request->fname);
        $lname = trim($request->lname ?? '');

        // Parse the check-in date and time from the request
        $checkIn = Carbon::parse($request->check_in);
        // Check if the check-in time is in the past (more than 5 minutes ago) and return an error if so
        if ($checkIn->lt(now()->subMinutes(5))) {
            return back()->withErrors(['check_in' => 'The arrival date and time cannot be in the past.'])->withInput();

        }
        // Calculate the check-out time based on the check-in time and the stay type (short stay or overnight)
        $checkOut = $checkIn->copy()->addHours($isShort ? (int) $request->short_hours : 24);

        // Server-side availability check: ensure no selected room has a conflicting stay
        $conflicting = $availability->conflictingRoomIds($checkIn, $checkOut, $request->room_ids);
        if (! empty($conflicting)) {
            $roomList = implode(', ', array_map(fn ($id) => 'Room '.str_pad($id, 2, '0', STR_PAD_LEFT), $conflicting));

            return back()
                ->withErrors(['room_ids' => "The following room(s) are no longer available for the selected period: {$roomList}. Please select different rooms."])
                ->withInput();
        }
        // Create a new guest record
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => $request->phone,
        ]);
        // Create a new reservation record
        $reservation = Reservation::create([
            'guest_name' => trim($fname.' '.$lname),
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
            'payment_method' => $request->input('payment_method', 'cash'),
            'ewallet_type' => $request->payment_method === 'ewallet' ? $request->ewallet_type : null,
            'reference_number' => $request->payment_method === 'ewallet' ? $request->reference_number : null,
        ]);

        // Record Reservation Activity Log
        ActivityLogService::logReservation($reservation);

        foreach ($request->room_ids as $roomId) {
            $isOccupied = Booking::where(function ($q) use ($roomId) {
                $q->whereJsonContains('booked_rooms', (string) $roomId)
                    ->orWhereJsonContains('booked_rooms', (int) $roomId);
            })->where('status', 'checked_in')->exists();

            $isBlocked = RoomBlock::where('room_id', $roomId)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->exists();

            if (! $isOccupied && ! $isBlocked) {
                Room::where('id', $roomId)->update(['status' => 'reserved']);
            }
        }

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Reservation created successfully.');
        // End of store method
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
        $reservation = Reservation::findOrFail($id);
        $roomIds = $reservation->selected_rooms ?? [];
        $wasReserved = $reservation->status === 'reserved';
        $reservation->delete();

        if ($wasReserved) {
            foreach ($roomIds as $roomId) {
                $hasOtherRes = Reservation::where('status', 'reserved')
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('selected_rooms', (string) $roomId)
                            ->orWhereJsonContains('selected_rooms', (int) $roomId);
                    })
                    ->exists();

                $hasActiveBooking = Booking::where('status', 'checked_in')
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('booked_rooms', (string) $roomId)
                            ->orWhereJsonContains('booked_rooms', (int) $roomId);
                    })
                    ->exists();

                $hasBlock = RoomBlock::where('room_id', $roomId)
                    ->where('is_active', true)
                    ->where('end_date', '>=', now())
                    ->exists();

                if (! $hasOtherRes && ! $hasActiveBooking && ! $hasBlock) {
                    Room::where('id', $roomId)->update(['status' => 'available']);
                }
            }
        }

        return redirect()->route('reservation.index')->with('success', 'Reservation deleted successfully.');
    }

    // This function retrieves available rooms based on the specified date range in the request. It checks for overlapping reservations and bookings, excluding those that are cancelled or checked in/out, and returns a list of available rooms.
    public function getAvailableRooms(Request $request, RoomAvailabilityService $availability)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if (! $start || ! $end) {
            return response()->json([]);
        }

        $startParsed = Carbon::parse($start);
        $endParsed = Carbon::parse($end);

        if ($endParsed->lte($startParsed)) {
            return response()->json([]);
        }

        $bookedRoomIds = $availability->conflictingRoomIds($startParsed, $endParsed);

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
            'deposit' => (float) ($reservation->deposit ?? 0),
            'payment_method' => $reservation->payment_method ?? 'cash',
            'ewallet_type' => $reservation->ewallet_type,
            'reference_number' => $reservation->reference_number,
        ]);

        if ((float) ($reservation->deposit ?? 0) > 0 && (! $booking->deposit || (float) $booking->deposit <= 0)) {
            $booking->update(['deposit' => (float) $reservation->deposit]);
        }

        $reservation->update(['status' => 'checked_in']);

        // Record Check-in Booking Activity Log
        ActivityLogService::logBooking($booking, null, 'check_in');

        return redirect()->route('reservation.index')
            ->with('success', 'Guest checked in. Total: ₱'.number_format($price['total'], 2));
    }

    /** Keeps the row (status Cancelled) and frees the rooms */
    public function cancel(int $id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status === 'reserved') {
            $reservation->update(['status' => 'cancelled']);

            // Record Cancellation Activity Log
            ActivityLogService::logCancellation($reservation);

            foreach ($reservation->selected_rooms ?? [] as $roomId) {
                $hasOtherRes = Reservation::where('status', 'reserved')
                    ->where('id', '!=', $reservation->id)
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('selected_rooms', (string) $roomId)
                            ->orWhereJsonContains('selected_rooms', (int) $roomId);
                    })
                    ->exists();

                $hasActiveBooking = Booking::where('status', 'checked_in')
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('booked_rooms', (string) $roomId)
                            ->orWhereJsonContains('booked_rooms', (int) $roomId);
                    })
                    ->exists();

                $hasBlock = RoomBlock::where('room_id', $roomId)
                    ->where('is_active', true)
                    ->where('end_date', '>=', now())
                    ->exists();

                if (! $hasOtherRes && ! $hasActiveBooking && ! $hasBlock) {
                    Room::where('id', $roomId)->update(['status' => 'available']);
                }
            }
        }

        return redirect()->route('reservation.index', ['filter' => 'cancelled'])
            ->with('success', 'Reservation cancelled.');
    }
}
