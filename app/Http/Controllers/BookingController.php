<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Charge;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController
{
    // Display a listing of bookings with optional filtering.
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Booking::with(['guest', 'invoice.paymentLogs', 'charges'])
            ->orderByRaw("CASE WHEN status = 'checked_in' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($filter === 'in_house') {
            $query->where('status', 'checked_in')
                ->whereHas('invoice', fn ($q) => $q->where('status', 'unpaid'));
        } elseif ($filter === 'overdue') {
            $now = now()->setTimezone('Asia/Manila');
            $query->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_out_datetime', '<', $now)
                ->where(function ($q) {
                    $q->whereDoesntHave('invoice')
                        ->orWhereHas('invoice', fn ($i) => $i->where('status', '!=', 'paid'));
                });
        } elseif ($filter === 'completed') {
            $query->where('status', '!=', 'cancelled')
                ->where(function ($q) {
                    $q->where('status', 'checked_out')
                        ->orWhereHas('invoice', fn ($i) => $i->where('status', 'paid'));
                });
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $bookings = $query->paginate(8)->withQueryString();

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
                'checkout_time' => $checkOut->format('h:i A'),
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

        return view('Receptionist.content.booking', compact('bookings', 'filter', 'dueGuests', 'occupiedRoomsList', 'availableAmenities'));
    }

    // Store a new booking in the database.
    public function store(Request $request, BookingService $bookings, RoomAvailabilityService $availability)
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
            'payment_method' => 'nullable|in:cash,ewallet',
            'ewallet_type' => 'nullable|required_if:payment_method,ewallet|string|max:50',
            'reference_number' => 'nullable|required_if:payment_method,ewallet|string|max:100',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $isShort = $request->stay_type === 'short_stay';
        $checkOut = $checkIn->copy()->addHours($isShort ? (int) $request->short_hours : 24);

        // Server-side availability check: ensure no selected room has a conflicting stay
        $conflicting = $availability->conflictingRoomIds($checkIn, $checkOut, $request->room_ids);
        if (! empty($conflicting)) {
            $roomList = implode(', ', array_map(fn ($id) => 'Room '.str_pad($id, 2, '0', STR_PAD_LEFT), $conflicting));

            return back()
                ->withErrors(['room_ids' => "The following room(s) are no longer available for the selected period: {$roomList}. Please select different rooms."])
                ->withInput();
        }

        // Create a new guest record in the database using the validated request data
        $guest = Guest::create([
            'fname' => trim($request->fname),
            'lname' => trim($request->lname ?? ''),
            'phone' => $request->phone,
        ]);

        if ($request->action_type === 'reserved') {
            Reservation::create([
                'guest_name' => trim($request->fname.' '.($request->lname ?? '')),
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

            return redirect()->back()->with('success', 'Reservation created successfully.');
        }

        // Create a new booking record in the database using the validated request data and the newly created guest's ID
        [$booking, $price] = $bookings->create([
            'guest_id' => $guest->id,
            'phone' => $request->phone,
            'check_in' => $checkIn,
            'stay_type' => $request->stay_type,
            'short_hours' => $request->short_hours,
            'with_breakfast' => $request->boolean('with_breakfast'),
            'extra_beds' => $request->input('extra_beds', 0),
            'discount_type' => $request->discount_type,
            'room_ids' => $request->room_ids,
            'guests' => $request->guests ?? 1,
            'status' => 'checked_in',
            'payment_method' => $request->input('payment_method', 'cash'),
            'ewallet_type' => $request->payment_method === 'ewallet' ? $request->ewallet_type : null,
            'reference_number' => $request->payment_method === 'ewallet' ? $request->reference_number : null,
        ]);

        // Generate an invoice for the booking
        return redirect()->back()->with('success', 'Booking saved. Total: ₱'.number_format($price['total'], 2));
        // End of recent edits
    }

    // Update the status of a booking and its associated invoice.
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:checked_in,checked_out,cancelled',
        ]);

        $booking = Booking::findOrFail($id);
        $newStatus = $request->status;
        $booking->update(['status' => $newStatus]);

        if ($newStatus === 'checked_in') {
            Room::whereIn('id', $booking->booked_rooms ?? [])->update(['status' => 'occupied']);
        } elseif (in_array($newStatus, ['checked_out', 'cancelled'])) {
            foreach ($booking->booked_rooms ?? [] as $roomId) {
                $hasActive = Booking::where('status', 'checked_in')
                    ->where('id', '!=', $booking->id)
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('booked_rooms', (string) $roomId)
                            ->orWhereJsonContains('booked_rooms', (int) $roomId);
                    })->exists();

                $hasRes = Reservation::where('status', 'reserved')
                    ->where(function ($q) use ($roomId) {
                        $q->whereJsonContains('selected_rooms', (string) $roomId)
                            ->orWhereJsonContains('selected_rooms', (int) $roomId);
                    })->exists();

                $hasBlock = RoomBlock::where('room_id', $roomId)
                    ->where('is_active', true)
                    ->where('end_date', '>=', now())
                    ->exists();

                if (! $hasActive && ! $hasBlock) {
                    Room::where('id', $roomId)->update(['status' => $hasRes ? 'reserved' : 'available']);
                }
            }
        }

        return back()->with('success', 'Booking status updated.');
    }

    /** Adds amenities to an unpaid booking's bill and recalculates the total and the discount */
    public function addAmenities(Request $request, int $id, InvoiceService $invoices)
    {
        $request->validate([
            'qty' => 'required|array',
            'qty.*' => 'nullable|integer|min:0|max:20',
        ]);

        $booking = Booking::with('invoice')->findOrFail($id);

        if ($booking->status === 'cancelled' || $booking->status === 'checked_out' || optional($booking->invoice)->status === 'paid') {
            return back()->withErrors(['amenities' => 'Amenities can only be added to an active, unpaid booking.']);
        }

        $catalog = config('hotel.amenities', []);
        $added = 0;

        foreach ($request->qty as $key => $qty) {
            $qty = (int) $qty;

            if ($qty < 1) {
                continue;
            }

            $label = null;
            $price = 0;

            if (is_numeric($key) && ($dbAmenity = Amenity::find($key))) {
                if (! $dbAmenity->offered) {
                    continue;
                }
                $label = $dbAmenity->name;
                $price = (float) $dbAmenity->price;
            } elseif (isset($catalog[$key])) {
                $label = $catalog[$key]['label'];
                $price = (float) $catalog[$key]['price'];
            }

            if (! $label) {
                continue;
            }

            Charge::create([
                'booking_id' => $booking->id,
                'type' => 'amenity',
                'description' => $label,
                'quantity' => $qty,
                'unit_price' => $price,
                'amount' => $qty * $price,
                'is_paid' => false,
            ]);

            if ($booking->invoice) {
                AvailableAmenity::create([
                    'booking_id' => $booking->id,
                    'invoice_id' => $booking->invoice->id,
                    'item_name' => $label,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_amount' => $qty * $price,
                    'is_available' => true,
                ]);
            }

            $added++;
        }

        if ($added === 0) {
            return back()->withErrors(['amenities' => 'Choose at least one amenity (quantity 1 or more).']);
        }

        $invoices->recalculate($booking);

        $total = Invoice::where('booking_id', $booking->id)->value('grand_total');

        return back()->with('success', 'Amenities added. New total: ₱'.number_format($total, 2));
    }

    /**
     * Add an amenity to an occupied room's current booking from the Add Amenities modal.
     */
    public function addAmenityToRoom(Request $request, InvoiceService $invoices)
    {
        $request->validate([
            'room_id' => 'required',
            'amenity_id' => 'required|exists:amenities,id',
            'quantity' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string|max:255',
        ]);

        $roomId = $request->room_id;
        $amenity = Amenity::findOrFail($request->amenity_id);
        if (! $amenity->offered) {
            return back()->withErrors(['amenity_id' => 'The selected amenity is not currently offered.']);
        }
        $qty = max(1, (int) $request->input('quantity', 1));
        $unitPrice = (float) $amenity->price;
        $totalAmount = $qty * $unitPrice;

        $now = now()->setTimezone('Asia/Manila');
        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::with(['guest', 'invoice'])->find($request->booking_id);
        }

        if (! $booking) {
            $booking = Booking::with(['guest', 'invoice'])
                ->where(function ($q) use ($roomId) {
                    $q->whereJsonContains('booked_rooms', (string) $roomId)
                        ->orWhereJsonContains('booked_rooms', (int) $roomId);
                })
                ->where('status', 'checked_in')
                ->where('check_in_datetime', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where('check_out_datetime', '>=', $now)
                        ->orWhere('status', 'checked_in');
                })
                ->orderByDesc('check_in_datetime')
                ->first();
        }

        if (! $booking) {
            return back()->withErrors(['amenity' => 'No active occupied booking found for Room '.str_pad($roomId, 2, '0', STR_PAD_LEFT).'.']);
        }

        $description = $amenity->name;
        if ($request->filled('notes')) {
            $description .= ' ('.trim($request->notes).')';
        }

        // 1. Record billing charge
        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'amenity',
            'description' => $description,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'amount' => $totalAmount,
            'is_paid' => false,
        ]);

        // 2. Ensure invoice exists and record in available_amenities for billing modal
        if (! $booking->invoice) {
            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-'.strtoupper(uniqid()),
                'room_charges' => 0,
                'extra_charges_total' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'status' => 'unpaid',
                'due_date' => $booking->check_out_datetime ? Carbon::parse($booking->check_out_datetime)->toDateString() : now()->toDateString(),
            ]);
            $booking->refresh();
        }

        AvailableAmenity::create([
            'booking_id' => $booking->id,
            'invoice_id' => $booking->invoice->id,
            'item_name' => $description,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'is_available' => true,
        ]);

        // 3. Recalculate invoice totals
        $invoices->recalculate($booking);

        $guestName = $booking->guest ? trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')) : 'Guest';
        $roomNumStr = str_pad($roomId, 2, '0', STR_PAD_LEFT);

        return back()->with('success', "Amenity '{$amenity->name}' (x{$qty} - ₱".number_format($totalAmount, 2).") successfully added to Room {$roomNumStr} ({$guestName}).");
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
