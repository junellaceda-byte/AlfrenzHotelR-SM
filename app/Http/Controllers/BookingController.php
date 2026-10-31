<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Services\PricingService;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\Request;

class BookingController
{
    // Display a listing of bookings with optional filtering.
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Booking::with(['guest', 'invoice.paymentLogs', 'charges'])->orderByDesc('check_in_datetime');

        if ($filter === 'in_house') {
            $query->where('status', 'checked_in')
                  ->whereHas('invoice', fn ($q) => $q->where('status', 'unpaid'));
        } elseif ($filter === 'completed') {
            $query->where('status', '!=', 'cancelled')
                  ->where(function ($q) {
                      $q->where('status', 'checked_out')
                        ->orWhereHas('invoice', fn ($i) => $i->where('status', 'paid'));
                  });
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $bookings = $query->paginate(30)->withQueryString();

        return view('content.booking', compact('bookings', 'filter'));
    }
    // Store a new booking in the database.
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
    // Update the status of a booking and its associated invoice.
    /** Adds amenities to an unpaid booking's bill and recalculates the total and the discount */
    public function addAmenities(Request $request, int $id, InvoiceService $invoices)
    {
        $request->validate([
            'qty' => 'required|array',
            'qty.*' => 'nullable|integer|min:0|max:20',
        ]);

        $booking = Booking::with('invoice')->findOrFail($id);

        if ($booking->status === 'cancelled' || optional($booking->invoice)->status === 'paid') {
            return back()->withErrors(['amenities' => 'Amenities can only be added to an active, unpaid booking.']);
        }

        $catalog = config('hotel.amenities');
        $added = 0;

        foreach ($request->qty as $key => $qty) {
            $qty = (int) $qty;

            if ($qty < 1 || !isset($catalog[$key])) {
                continue;
            }

            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'amenity',
                'description' => $catalog[$key]['label'],
                'quantity' => $qty,
                'unit_price' => $catalog[$key]['price'],
                'amount' => $qty * $catalog[$key]['price'],
            ]);
            $added++;
        }

        if ($added === 0) {
            return back()->withErrors(['amenities' => 'Choose at least one amenity (quantity 1 or more).']);
        }

        $invoices->recalculate($booking);

        $total = Invoice::where('booking_id', $booking->id)->value('grand_total');

        return back()->with('success', 'Amenities added. New total: ₱' . number_format($total, 2));
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
