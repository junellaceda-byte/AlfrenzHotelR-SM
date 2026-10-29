<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Services\PricingService;
use App\Services\BookingService;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\Request;

class BookingController
{
    public function index(Request $request)
    {
        /* Start building the query for bookings, eager loading the guest
        relationship and ordering by latest*/
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
        // Paginate the results to show 30 bookings per page
        $bookings = $query->paginate(30);

        return view('content.booking', compact('bookings'));
    }

        public function store(Request $request, BookingService $bookings)
    {
        // Validate the incoming request data
        $request->validate([
            'fname' => 'required|string|max:30',
            'lname' => 'nullable|string|max:30',
            'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:15',
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'integer|exists:rooms,id',
            'stay_type' => 'required|in:short_stay,overnight',
            'short_hours' => 'required_if:stay_type,short_stay|nullable|in:3,6,12',
            'with_breakfast' => 'nullable|boolean',
            'extra_beds' => 'nullable|integer|min:0|max:5',
            'discount_type' => 'nullable|in:senior,pwd',
            'check_in' => 'required|date|after_or_equal:today|before:tomorrow',
            'check_out' => 'required|date|after:check_in',
            'action_type' => 'required|in:reserved,check_in',
            'guests' => 'nullable|integer|min:1|max:20',
        ]);
        // Create a new guest record in the database using the validated request data
        $guest = Guest::create([
            'fname' => trim($request->fname),
            'lname' => trim($request->lname ?? ''),
            'phone' => $request->phone,
        ]);
        // Create a new booking record in the database using the validated request data and the newly created guest's ID
        [$booking, $price] = $bookings->create([
            'guest_id' => $guest->id,
            'phone' => $request->phone,
            'check_in' => \Carbon\Carbon::parse($request->check_in),
            'stay_type' => $request->stay_type,
            'short_hours' => $request->short_hours,
            'with_breakfast' => $request->boolean('with_breakfast'),
            'extra_beds' => $request->input('extra_beds', 0),
            'discount_type' => $request->discount_type,
            'room_ids' => $request->room_ids,
            'guests' => $request->guests ?? 1,
            'status' => $request->action_type === 'check_in' ? 'checked_in' : 'booked',
        ]);
        // Generate an invoice for the booking
        return redirect()->back()->with('success', 'Booking saved. Total: ₱' . number_format($price['total'], 2));
        //End of recent edits
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
