<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\Request;

class BookingController
{
    public function index(Request $request)
    {
        $query = Booking::with('guest')->latest();

        if ($request->filled('search')) {
            $query->whereHas('guest', function ($q) use ($request) {
                $q->where('fname', 'like', '%' . $request->search . '%')
                  ->orWhere('lname', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('check_in_datetime', '>=', $request->start_date)
                  ->whereDate('check_in_datetime', '<=', $request->end_date);
            });
        }

        $bookings = $query->paginate(30);

        return view('content.booking', compact('bookings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fname' => 'nullable|string|min:2|max:30',
            'lname' => 'nullable|string|min:2|max:30',
            'phone' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:15',
            'room_ids' => 'required|array',
            'room_ids.*' => 'integer|exists:rooms,id',
            'stay_type' => 'required|in:short_stay,overnight',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'action_type' => 'required|in:reserved,check_in',
            'guests' => 'nullable|integer|min:1|max:20',
        ]);

        $fname = trim($request->fname) ?: 'Walk-in';
        $lname = trim($request->lname) ?: 'Guest';

        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => $request->phone,
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => $request->phone,
            'check_in_datetime' => $request->check_in,
            'check_out_datetime' => $request->check_out,
            'booked_rooms' => $request->room_ids,
            'guests' => $request->guests ?? 1,
            'stay_type' => $request->stay_type,
            'status' => $request->action_type === 'check_in' ? 'checked_in' : 'booked',
        ]); 

        if ($request->action_type === 'check_in') {
            Room::whereIn('id', $request->room_ids)->update(['status' => 'occupied']);
        }

        // Calculate room charges using PHP native dates (no Carbon)
        $rooms = Room::whereIn('id', $request->room_ids)->get();
        $roomCharges = 0;

        if ($request->stay_type === 'overnight') {
            $checkIn = new \DateTime(date('Y-m-d', strtotime($request->check_in)));
            $checkOut = new \DateTime(date('Y-m-d', strtotime($request->check_out)));
            $nights = max(1, (int) $checkIn->diff($checkOut)->days);

            foreach ($rooms as $room) {
                $roomCharges += $room->overnight_rate * $nights;
            }
        } else {
            foreach ($rooms as $room) {
                $roomCharges += $room->short_stay_rate;
            }
        }

        // Auto-create unpaid Invoice — makes the booking appear in Billing > Pending Payments
        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-'.strtoupper(uniqid()),
            'room_charges' => $roomCharges,
            'grand_total' => $roomCharges,
            'status' => 'unpaid',
            'due_date' => date('Y-m-d', strtotime($request->check_out)),
        ]);

        return redirect()->back()->with('success', 'Booking created & recorded successfully.');
    }

    /**
     * Update the guest info linked to a booking.
     */
    public function update(Request $request, int $id)
    {
        $booking = Booking::with('guest')->findOrFail($id);

        $request->validate([
            'fname' => 'nullable|string|min:2|max:30',
            'lname' => 'nullable|string|min:2|max:30',
            'phone' => 'nullable|string|max:15',
        ]);

        $booking->guest->update([
            'fname' => trim($request->fname) ?: $booking->guest->fname,
            'lname' => trim($request->lname) ?: $booking->guest->lname,
            'phone' => $request->phone,
        ]);

        return redirect()->route('booking.index')->with('success', 'Booking updated successfully.');
    }

    /**
     * Delete a booking and its associated invoice.
     */
    public function destroy(int $id)
    {
        Booking::findOrFail($id)->delete();

        return redirect()->route('booking.index')->with('success', 'Booking deleted successfully.');
    }
}
