<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Services\PricingService;
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

    public function store(Request $request, PricingService $pricing)
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
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'action_type' => 'required|in:reserved,check_in',
            'guests' => 'nullable|integer|min:1|max:20',
        ]);
        // Determine if it's a short stay or overnight stay
        $isShort = $request->stay_type === 'short_stay';
        $withBreakfast = $request->boolean('with_breakfast');
        $extraBeds = $isShort ? 0 : (int) $request->input('extra_beds', 0);

        // Check-out is always worked out here: check-in + chosen hours (or 24 hours)
        $checkIn = \Carbon\Carbon::parse($request->check_in);
        $checkOut = $checkIn->copy()->addHours($isShort ? (int) $request->short_hours : 24);

        // Create the guest record
        $guest = Guest::create([
            'fname' => trim($request->fname),
            'lname' => trim($request->lname ?? ''),
            'phone' => $request->phone,
        ]);
        // Calculate the price based on the provided details
        $price = $pricing->calculate([
            'stay_type' => $request->stay_type,
            'short_hours' => $request->short_hours,
            'with_breakfast' => $withBreakfast,
            'extra_beds' => $extraBeds,
            'discount_type' => $request->discount_type,
        ], count($request->room_ids));
        // Create the booking record
        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => $request->phone,
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'booked_rooms' => $request->room_ids,
            'guests' => $request->guests ?? 1,
            'stay_type' => $request->stay_type,
            'short_stay_hours' => $isShort ? (int) $request->short_hours : null,
            'with_breakfast' => $withBreakfast,
            'extra_beds' => $extraBeds,
            'discount_type' => $request->discount_type ?: null,
            'nights' => $isShort ? null : 1,
            'status' => $request->action_type === 'check_in' ? 'checked_in' : 'booked',
        ]);
        // Update the status of the rooms to 'occupied' if the action type is 'check_in'
        if ($request->action_type === 'check_in') {
            Room::whereIn('id', $request->room_ids)->update(['status' => 'occupied']);
        }

        // One charge line per room, plus one line for extra beds
        $stayLabel = $isShort
            ? $request->short_hours . '-hour short stay'
            : '24-hour stay' . ($withBreakfast ? ' with breakfast' : '');
        // Create charge records for each room booked
        foreach (Room::whereIn('id', $request->room_ids)->get() as $room) {
            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'room',
                'description' => 'Room ' . str_pad($room->id, 2, '0', STR_PAD_LEFT) . ' - ' . $stayLabel,
                'quantity' => 1,
                'unit_price' => $price['room_price'],
                'amount' => $price['room_price'],
            ]);
        }
        // Create a charge record for extra beds if any
        if ($extraBeds > 0) {
            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'extra_bed',
                'description' => 'Extra bed',
                'quantity' => $extraBeds,
                'unit_price' => $price['bed_price'],
                'amount' => $price['beds'],
            ]);
        }

        // Unpaid invoice: shows in Billing > Pending Payments (payment is taken at check-out)
        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'room_charges' => $price['rooms'],
            'extra_charges_total' => $price['beds'],
            'discount_amount' => $price['discount'],
            'tax_amount' => 0,
            'grand_total' => $price['total'],
            'status' => 'unpaid',
            'due_date' => $checkOut->toDateString(),
        ]);

        return redirect()->back()->with('success', 'Booking saved. Total: ₱' . number_format($price['total'], 2));
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
