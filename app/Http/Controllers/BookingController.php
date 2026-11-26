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
use App\Services\ActivityLogService;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\RoomAvailabilityService;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController
{
    // Display a listing of bookings with optional filtering.
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->input('search', ''));

        $query = Booking::with(['guest', 'invoice.paymentLogs', 'charges'])
            ->orderByRaw("CASE WHEN status = 'checked_in' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                // Booking ID / Code e.g. "BK-0001", "BK-1", "0001", "1"
                if (preg_match('/^(?:BK-?)?0*(\d+)$/i', $search, $m)) {
                    $q->orWhere('id', (int) $m[1]);
                }

                $q->orWhere('phone', 'like', "%{$search}%");

                // Room numbers: e.g. "01", "1", "Room 1"
                if (preg_match('/\b\d+\b/', $search, $rMatch)) {
                    $rNum = (int) $rMatch[0];
                    $q->orWhere('booked_rooms', 'like', "%\"{$rNum}\"%")
                        ->orWhere('booked_rooms', 'like', "%{$rNum}%")
                        ->orWhere('booked_rooms', 'like', '%"'.str_pad((string) $rNum, 2, '0', STR_PAD_LEFT).'"%');
                }
                $q->orWhere('booked_rooms', 'like', "%{$search}%");

                // Walk-in search
                if (stripos($search, 'walk') !== false) {
                    $q->orWhereDoesntHave('guest');
                }

                // Status search
                if (stripos($search, 'cancel') !== false) {
                    $q->orWhere('status', 'cancelled');
                }
                if (stripos($search, 'complet') !== false) {
                    $q->orWhere(function ($comp) {
                        $comp->where('status', 'checked_out')
                            ->orWhereHas('invoice', fn ($i) => $i->where('status', 'paid'));
                    });
                }
                if (stripos($search, 'in house') !== false || stripos($search, 'in_house') !== false) {
                    $q->orWhere(function ($ih) {
                        $ih->where('status', 'checked_in')
                            ->whereHas('invoice', fn ($i) => $i->where('status', 'unpaid'));
                    });
                }
                if (stripos($search, 'overdue') !== false) {
                    $now = now()->setTimezone('Asia/Manila');
                    $q->orWhere(function ($ov) use ($now) {
                        $ov->whereNotIn('status', ['cancelled', 'checked_out'])
                            ->where('check_out_datetime', '<', $now);
                    });
                }

                // Guest Name (first name, last name, full name)
                $q->orWhereHas('guest', function ($gQ) use ($search) {
                    $gQ->where(function ($nameQ) use ($search) {
                        $nameQ->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");

                        // Support matching "First Last" across both fields
                        $words = array_values(array_filter(preg_split('/\s+/', $search)));
                        if (count($words) > 1) {
                            $nameQ->orWhere(function ($multiQ) use ($words) {
                                foreach ($words as $word) {
                                    $multiQ->where(function ($subQ) use ($word) {
                                        $subQ->where('fname', 'like', "%{$word}%")
                                            ->orWhere('lname', 'like', "%{$word}%");
                                    });
                                }
                            });
                        }
                    });
                });
            });
        }

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
            $reservation = Reservation::create([
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
            'deposit' => (float) $request->input('deposit', 0),
            'payment_method' => $request->input('payment_method', 'cash'),
            'ewallet_type' => $request->payment_method === 'ewallet' ? $request->ewallet_type : null,
            'reference_number' => $request->payment_method === 'ewallet' ? $request->reference_number : null,
        ]);

        // Record Immediate Booking Activity Log
        ActivityLogService::logBooking($booking, null, 'immediate');

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

        if ($newStatus === 'cancelled') {
            ActivityLogService::logCancellation($booking);
        }

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
        // If extra hours were submitted through this route for compatibility
        if ($request->has('extra_hours') || $request->has('hours')) {
            return $this->addExtraHours($request, $id, $invoices);
        }

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
     * Adds extra hours to an active unpaid booking, extending checkout date/time and stay duration,
     * and adding the charge (₱150/hr) to billing balance.
     */
    public function addExtraHours(Request $request, int $id, InvoiceService $invoices)
    {
        $hours = (int) ($request->input('extra_hours') ?? $request->input('hours', 0));

        if ($hours < 1) {
            return back()->withErrors(['extra_hours' => 'Please select at least 1 extra hour.']);
        }

        $booking = Booking::with('invoice')->findOrFail($id);

        if ($booking->status === 'cancelled' || $booking->status === 'checked_out' || optional($booking->invoice)->status === 'paid') {
            return back()->withErrors(['extra_hours' => 'Extra hours can only be added to an active, unpaid booking.']);
        }

        $ratePerHour = 150.00;
        $totalAmount = $hours * $ratePerHour;

        // 1. Extend checkout datetime
        $currentCheckout = Carbon::parse($booking->check_out_datetime);
        $newCheckout = $currentCheckout->copy()->addHours($hours);

        // 2. Extend room stay duration
        $updates = [
            'check_out_datetime' => $newCheckout,
        ];

        if ($booking->stay_type === 'short_stay') {
            $updates['short_stay_hours'] = ((int) ($booking->short_stay_hours ?: 0)) + $hours;
        }

        $booking->update($updates);

        // 3. Create Charge record (type extra_hours)
        $description = 'Extra Hours ('.$hours.' hr'.($hours > 1 ? 's' : '').')';
        Charge::create([
            'booking_id' => $booking->id,
            'type' => 'extra_hours',
            'description' => $description,
            'quantity' => $hours,
            'unit_price' => $ratePerHour,
            'amount' => $totalAmount,
            'is_paid' => false,
        ]);

        // 4. Create AvailableAmenity entry for invoice itemization compatibility
        if ($booking->invoice) {
            AvailableAmenity::create([
                'booking_id' => $booking->id,
                'invoice_id' => $booking->invoice->id,
                'item_name' => $description,
                'quantity' => $hours,
                'unit_price' => $ratePerHour,
                'total_amount' => $totalAmount,
                'is_available' => true,
            ]);
        }

        // 5. Recalculate invoice billing balance
        $invoices->recalculate($booking);

        $grandTotal = Invoice::where('booking_id', $booking->id)->value('grand_total');

        return back()->with('success', "{$hours} extra hour(s) added successfully. Checkout extended to {$newCheckout->format('M d, Y g:i A')}. New total: ₱".number_format($grandTotal, 2));
    }

    /**
     * Add one or multiple amenities to an occupied room's current booking from the Add Amenities modal.
     */
    public function addAmenityToRoom(Request $request, InvoiceService $invoices)
    {
        $request->validate([
            'room_id' => 'required',
            'amenity_id' => 'nullable|exists:amenities,id',
            'quantity' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string|max:255',
            'amenities' => 'nullable|array',
            'amenities.*.amenity_id' => 'required_with:amenities|exists:amenities,id',
            'amenities.*.quantity' => 'nullable|integer|min:1|max:20',
            'amenities.*.notes' => 'nullable|string|max:255',
            'amenities_json' => 'nullable|string',
        ]);

        $roomId = $request->room_id;

        // Collect items from multiple possible inputs (array, JSON string, or single amenity_id)
        $rawItems = [];
        if ($request->has('amenities') && is_array($request->amenities)) {
            $rawItems = $request->amenities;
        } elseif ($request->filled('amenities_json')) {
            $decoded = json_decode($request->amenities_json, true);
            if (is_array($decoded)) {
                $rawItems = $decoded;
            }
        } elseif ($request->filled('amenity_id')) {
            $rawItems = [[
                'amenity_id' => $request->amenity_id,
                'quantity' => $request->input('quantity', 1),
                'notes' => $request->input('notes'),
            ]];
        }

        $items = [];
        foreach ($rawItems as $rawItem) {
            if (! empty($rawItem['amenity_id'])) {
                $items[] = [
                    'amenity_id' => (int) $rawItem['amenity_id'],
                    'quantity' => max(1, (int) ($rawItem['quantity'] ?? 1)),
                    'notes' => isset($rawItem['notes']) ? trim((string) $rawItem['notes']) : null,
                ];
            }
        }

        if (empty($items)) {
            return back()->withErrors(['amenity_id' => 'Please select at least one amenity.']);
        }

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

        // Validate that all requested amenities are currently offered
        foreach ($items as $item) {
            $amenity = Amenity::find($item['amenity_id']);
            if (! $amenity || ! $amenity->offered) {
                return back()->withErrors(['amenity_id' => 'The selected amenity is not currently offered.']);
            }
        }

        // 1. Ensure invoice exists for the booking
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

        // 2. Record billing charges and available amenities in a single transaction
        $addedDetails = [];
        $grandAddedTotal = 0.0;

        DB::transaction(function () use ($items, $booking, &$addedDetails, &$grandAddedTotal) {
            foreach ($items as $item) {
                $amenity = Amenity::findOrFail($item['amenity_id']);
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = (float) $amenity->price;
                $totalAmount = $qty * $unitPrice;

                $description = $amenity->name;
                if (! empty($item['notes'])) {
                    $description .= ' ('.trim($item['notes']).')';
                }

                Charge::create([
                    'booking_id' => $booking->id,
                    'type' => 'amenity',
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $totalAmount,
                    'is_paid' => false,
                ]);

                AvailableAmenity::create([
                    'booking_id' => $booking->id,
                    'invoice_id' => $booking->invoice->id,
                    'item_name' => $description,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'is_available' => true,
                ]);

                $grandAddedTotal += $totalAmount;
                $addedDetails[] = "{$amenity->name} (x{$qty})";
            }
        });

        // 3. Recalculate invoice totals
        $invoices->recalculate($booking);

        $guestName = $booking->guest ? trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')) : 'Guest';
        $roomNumStr = str_pad($roomId, 2, '0', STR_PAD_LEFT);

        if (count($items) === 1) {
            $firstAmenity = Amenity::find($items[0]['amenity_id']);
            $firstQty = $items[0]['quantity'];
            $firstTotal = $firstQty * (float) ($firstAmenity ? $firstAmenity->price : 0);
            $name = $firstAmenity ? $firstAmenity->name : 'Amenity';

            return back()->with('success', "Amenity '{$name}' (x{$firstQty} - ₱".number_format($firstTotal, 2).") successfully added to Room {$roomNumStr} ({$guestName}).");
        }

        $amenitiesSummary = implode(', ', $addedDetails);

        return back()->with('success', count($items)." amenities ({$amenitiesSummary} - Total: ₱".number_format($grandAddedTotal, 2).") successfully added to Room {$roomNumStr} ({$guestName}).");
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

    /**
     * Export Booking History records within the selected date range to an Excel-compatible CSV file
     * with a complete itemized breakdown of room charges, amenities, additional charges, discounts, and payments.
     */
    public function export(Request $request)
    {
        $now = now()->setTimezone('Asia/Manila');
        $preset = $request->input('preset');
        $fromInput = $request->input('from') ?? $request->input('from_date');
        $toInput = $request->input('to') ?? $request->input('to_date');
        $filter = $request->input('filter', 'all');
        $search = trim((string) $request->input('search', ''));

        if ($preset === 'last7') {
            $rangeStart = $now->copy()->subDays(6)->startOfDay();
            $rangeEnd = $now->copy()->endOfDay();
            $label = 'Last_7_Days';
        } elseif ($preset === 'lastMonth') {
            $rangeStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $rangeEnd = $rangeStart->copy()->endOfMonth();
            $label = 'Last_Month_'.$rangeStart->format('M_Y');
        } elseif ($preset === 'thisMonth') {
            $rangeStart = $now->copy()->startOfMonth();
            $rangeEnd = $rangeStart->copy()->endOfMonth();
            $label = 'This_Month_'.$rangeStart->format('M_Y');
        } elseif (! empty($fromInput) && ! empty($toInput)) {
            $rangeStart = Carbon::parse($fromInput, 'Asia/Manila')->startOfDay();
            $rangeEnd = Carbon::parse($toInput, 'Asia/Manila')->endOfDay();
            $label = $rangeStart->format('m_d_Y').'_to_'.$rangeEnd->format('m_d_Y');
        } elseif (! empty($fromInput)) {
            $rangeStart = Carbon::parse($fromInput, 'Asia/Manila')->startOfDay();
            $rangeEnd = $now->copy()->endOfDay();
            $label = 'From_'.$rangeStart->format('m_d_Y');
        } else {
            $rangeStart = $now->copy()->startOfMonth();
            $rangeEnd = $rangeStart->copy()->endOfMonth();
            $label = 'This_Month_'.$rangeStart->format('M_Y');
        }

        if ($rangeStart->gt($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        $query = Booking::with(['guest', 'invoice.paymentLogs', 'invoice.amenities', 'charges'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('check_in_datetime', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('check_out_datetime', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('created_at', [$rangeStart, $rangeEnd])
                    ->orWhereHas('invoice.paymentLogs', function ($plQ) use ($rangeStart, $rangeEnd) {
                        $plQ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                            ->orWhereBetween('created_at', [$rangeStart, $rangeEnd]);
                    });
            })
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (preg_match('/^(?:BK-?)?0*(\d+)$/i', $search, $m)) {
                    $q->orWhere('id', (int) $m[1]);
                }
                $q->orWhere('phone', 'like', "%{$search}%");
                if (preg_match('/\b\d+\b/', $search, $rMatch)) {
                    $rNum = (int) $rMatch[0];
                    $q->orWhere('booked_rooms', 'like', "%\"{$rNum}\"%")
                        ->orWhere('booked_rooms', 'like', "%{$rNum}%")
                        ->orWhere('booked_rooms', 'like', '%"'.str_pad((string) $rNum, 2, '0', STR_PAD_LEFT).'"%');
                }
                $q->orWhere('booked_rooms', 'like', "%{$search}%");
                if (stripos($search, 'walk') !== false) {
                    $q->orWhereDoesntHave('guest');
                }
                if (stripos($search, 'cancel') !== false) {
                    $q->orWhere('status', 'cancelled');
                }
                if (stripos($search, 'complet') !== false) {
                    $q->orWhere(function ($comp) {
                        $comp->where('status', 'checked_out')
                            ->orWhereHas('invoice', fn ($i) => $i->where('status', 'paid'));
                    });
                }
                if (stripos($search, 'in house') !== false || stripos($search, 'in_house') !== false) {
                    $q->orWhere(function ($ih) {
                        $ih->where('status', 'checked_in')
                            ->whereHas('invoice', fn ($i) => $i->where('status', 'unpaid'));
                    });
                }
                if (stripos($search, 'overdue') !== false) {
                    $nowManila = now()->setTimezone('Asia/Manila');
                    $q->orWhere(function ($ov) use ($nowManila) {
                        $ov->whereNotIn('status', ['cancelled', 'checked_out'])
                            ->where('check_out_datetime', '<', $nowManila);
                    });
                }
                $q->orWhereHas('guest', function ($gQ) use ($search) {
                    $gQ->where(function ($nameQ) use ($search) {
                        $nameQ->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                        $words = array_values(array_filter(preg_split('/\s+/', $search)));
                        if (count($words) > 1) {
                            $nameQ->orWhere(function ($multiQ) use ($words) {
                                foreach ($words as $word) {
                                    $multiQ->where(function ($subQ) use ($word) {
                                        $subQ->where('fname', 'like', "%{$word}%")
                                            ->orWhere('lname', 'like', "%{$word}%");
                                    });
                                }
                            });
                        }
                    });
                });
            });
        }

        if ($filter === 'in_house') {
            $query->where('status', 'checked_in')
                ->whereHas('invoice', fn ($q) => $q->where('status', 'unpaid'));
        } elseif ($filter === 'overdue') {
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

        $bookings = $query->get();
        $allRoomsKeyed = Room::all()->keyBy('id');

        $filename = 'Booking_History_'.$label.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($bookings, $rangeStart, $rangeEnd, $now, $allRoomsKeyed, $filter) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header Section
            fputcsv($handle, ['HOTEL BOOKING HISTORY REPORT - ITEMIZED PAYMENT BREAKDOWN']);
            fputcsv($handle, ['Date Range From', $rangeStart->format('m/d/Y')]);
            fputcsv($handle, ['Date Range To', $rangeEnd->format('m/d/Y')]);
            fputcsv($handle, ['Status Filter', ucfirst(str_replace('_', ' ', $filter))]);
            fputcsv($handle, ['Generated At', now()->setTimezone('Asia/Manila')->format('m/d/Y h:i:s A')]);
            fputcsv($handle, []);

            // Summary Metrics Calculation
            $totalRevenue = 0.0;
            $inHouseCount = 0;
            $completedCount = 0;
            $overdueCount = 0;
            $cancelledCount = 0;
            $totalRoomRevenue = 0.0;
            $totalAmenitiesRevenue = 0.0;
            $totalAdditionalChargesRevenue = 0.0;
            $totalDiscountsApplied = 0.0;

            // Process and cache details for each booking
            $processedBookings = [];

            foreach ($bookings as $b) {
                $invoice = $b->invoice;
                $log = $invoice ? ($invoice->paymentLogs->where('status', 'successful')->sortByDesc('id')->first() ?? $invoice->paymentLogs->sortByDesc('id')->first()) : null;

                $paid = $invoice && $invoice->status === 'paid';
                $cancelled = ($b->status === 'cancelled');
                $overdue = $b->check_out_datetime && Carbon::parse($b->check_out_datetime)->lt($now);
                $statusText = $cancelled ? 'Cancelled'
                    : (($paid || $b->status === 'checked_out') ? 'Completed'
                    : ($overdue ? 'Overdue' : 'In house'));

                if ($cancelled) {
                    $cancelledCount++;
                } elseif ($paid || $b->status === 'checked_out') {
                    $completedCount++;
                } elseif ($overdue) {
                    $overdueCount++;
                } else {
                    $inHouseCount++;
                }

                // 1. Room Charges Details
                $roomIds = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
                $roomNumbersList = [];
                foreach ($roomIds as $rId) {
                    $roomObj = $allRoomsKeyed->get($rId);
                    $roomNumbersList[] = $roomObj ? ('Room '.str_pad($roomObj->room_number, 2, '0', STR_PAD_LEFT)) : ('Room '.str_pad((string) $rId, 2, '0', STR_PAD_LEFT));
                }
                $roomNumbersStr = ! empty($roomNumbersList) ? implode(', ', $roomNumbersList) : '-';

                $nights = (int) ($b->nights ?: 1);
                if ($nights <= 0 && $b->check_in_datetime && $b->check_out_datetime) {
                    $nights = max(1, (int) Carbon::parse($b->check_in_datetime)->diffInDays(Carbon::parse($b->check_out_datetime)));
                }

                $roomChargesFromDB = $b->charges ? $b->charges->where('type', 'room') : collect();
                if ($roomChargesFromDB->isNotEmpty()) {
                    $totalRoomCharges = (float) $roomChargesFromDB->sum('amount');
                    $firstCharge = $roomChargesFromDB->first();
                    $roomRate = (float) ($firstCharge->unit_price > 0 ? $firstCharge->unit_price : ($totalRoomCharges / max(1, $nights * max(1, count($roomIds)))));
                } else {
                    $totalRoomCharges = (float) ($invoice->room_charges ?? 0);
                    $firstRoom = ! empty($roomIds) ? $allRoomsKeyed->get($roomIds[0]) : null;
                    if ($firstRoom) {
                        $roomRate = $b->stay_type === 'short_stay' ? (float) $firstRoom->short_stay_rate : (float) $firstRoom->overnight_rate;
                    } else {
                        $roomRate = $totalRoomCharges > 0 ? round($totalRoomCharges / max(1, $nights * max(1, count($roomIds))), 2) : 0.0;
                    }
                }

                // 2. Amenities Details
                $amenitiesList = [];
                $amenitiesChargesFromDB = $b->charges ? $b->charges->where('type', 'amenity') : collect();
                if ($amenitiesChargesFromDB->isNotEmpty()) {
                    foreach ($amenitiesChargesFromDB as $ac) {
                        $qty = max(1, (int) $ac->quantity);
                        $uPrice = (float) $ac->unit_price;
                        $tAmount = (float) $ac->amount;
                        $amenitiesList[] = [
                            'name' => $ac->description,
                            'quantity' => $qty,
                            'unit_price' => $uPrice,
                            'total_amount' => $tAmount,
                        ];
                    }
                } elseif ($invoice && $invoice->amenities && $invoice->amenities->isNotEmpty()) {
                    foreach ($invoice->amenities as $ia) {
                        $qty = max(1, (int) ($ia->quantity ?? 1));
                        $uPrice = (float) ($ia->unit_price ?? $ia->price ?? 0);
                        $tAmount = (float) ($ia->total_amount ?? ($qty * $uPrice));
                        $amenitiesList[] = [
                            'name' => $ia->item_name ?? 'Amenity',
                            'quantity' => $qty,
                            'unit_price' => $uPrice,
                            'total_amount' => $tAmount,
                        ];
                    }
                }
                $totalAmenityCharges = (float) collect($amenitiesList)->sum('total_amount');
                $amenitiesSummaryStr = ! empty($amenitiesList)
                    ? collect($amenitiesList)->map(fn ($a) => "{$a['name']} (x{$a['quantity']} @ ".number_format($a['unit_price'], 2).' = '.number_format($a['total_amount'], 2).')')->implode('; ')
                    : 'None';

                // 3. Additional Charges / Extra Fees
                $additionalChargesList = [];
                $extraChargesFromDB = $b->charges ? $b->charges->whereNotIn('type', ['room', 'amenity']) : collect();
                if ($extraChargesFromDB->isNotEmpty()) {
                    foreach ($extraChargesFromDB as $ec) {
                        $qty = max(1, (int) $ec->quantity);
                        $uPrice = (float) ($ec->unit_price ?? ($ec->amount / $qty));
                        $tAmount = (float) $ec->amount;
                        $additionalChargesList[] = [
                            'name' => $ec->description,
                            'quantity' => $qty,
                            'unit_price' => $uPrice,
                            'total_amount' => $tAmount,
                        ];
                    }
                }

                $accountedExtras = $totalAmenityCharges + collect($additionalChargesList)->sum('total_amount');
                $invoiceExtraCharges = (float) ($invoice->extra_charges_total ?? 0);
                if ($invoiceExtraCharges > $accountedExtras + 0.01) {
                    $diff = $invoiceExtraCharges - $accountedExtras;
                    $additionalChargesList[] = [
                        'name' => 'Other Billed Extra Charges',
                        'quantity' => 1,
                        'unit_price' => $diff,
                        'total_amount' => $diff,
                    ];
                }
                $totalAdditionalCharges = (float) collect($additionalChargesList)->sum('total_amount');
                $additionalChargesSummaryStr = ! empty($additionalChargesList)
                    ? collect($additionalChargesList)->map(fn ($e) => "{$e['name']} (x{$e['quantity']} @ ".number_format($e['unit_price'], 2).' = '.number_format($e['total_amount'], 2).')')->implode('; ')
                    : 'None';

                // 4. Discounts
                $discountType = $b->discount_type;
                $discountLabel = match ($discountType) {
                    'senior' => 'Senior Citizen (20%)',
                    'pwd' => 'PWD (20%)',
                    'special' => 'Special Discount',
                    'regular', 'none', null => 'None',
                    default => ucwords(str_replace('_', ' ', (string) $discountType)),
                };
                $discountAmount = (float) ($invoice->discount_amount ?? 0);
                if ($discountLabel === 'None' && $discountAmount > 0) {
                    $discountLabel = 'Applied Discount';
                }

                // 5. Payment Details
                $isPaid = $invoice && $invoice->status === 'paid';
                $amountPaid = 0.0;
                if ($log && $log->amount_paid > 0) {
                    $amountPaid = (float) $log->amount_paid;
                } elseif ($isPaid) {
                    $amountPaid = (float) ($invoice->grand_total ?? 0);
                } elseif ($b->deposit > 0) {
                    $amountPaid = (float) $b->deposit;
                }

                $paymentDate = 'Not paid yet';
                if ($log && ($log->paid_at || $log->created_at)) {
                    $paymentDate = SystemFormatService::formatDateTime($log->paid_at ?? $log->created_at);
                } elseif ($isPaid && $invoice->updated_at) {
                    $paymentDate = SystemFormatService::formatDateTime($invoice->updated_at);
                }

                $methodRaw = $log?->payment_method ?? $b->payment_method ?? '-';
                $cleanMethod = trim(strtolower((string) $methodRaw));
                $isEwallet = in_array($cleanMethod, ['ewallet', 'gcash', 'e-wallet', 'paymaya', 'paypal']);
                $paymentMethodFormatted = match ($cleanMethod) {
                    'ewallet', 'gcash', 'e-wallet', 'paymaya', 'paypal' => 'E-Wallet'.(! empty($b->ewallet_type) && strtolower($b->ewallet_type) !== 'ewallet' ? ' ('.strtoupper($b->ewallet_type).')' : ''),
                    'cash' => 'Cash',
                    'credit_card' => 'Credit Card',
                    'bank_transfer' => 'Bank Transfer',
                    '-', '' => '-',
                    default => ucfirst(str_replace('_', ' ', (string) $methodRaw)),
                };

                $refNo = $log?->reference_number ?? $b->reference_number ?? null;
                $ewalletRefFormatted = (! empty($refNo)) ? (string) $refNo : ($isEwallet ? 'Not specified' : 'N/A');

                if ($paid || $b->status === 'checked_out') {
                    $totalRevenue += $amountPaid;
                    $totalRoomRevenue += $totalRoomCharges;
                    $totalAmenitiesRevenue += $totalAmenityCharges;
                    $totalAdditionalChargesRevenue += $totalAdditionalCharges;
                    $totalDiscountsApplied += $discountAmount;
                }

                $processedBookings[] = [
                    'booking' => $b,
                    'booking_no' => 'BK-'.str_pad($b->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $b->guest?->fname ?? 'Walk-in',
                    'last_name' => $b->guest?->lname ?: '-',
                    'full_name' => trim(($b->guest?->fname ?? 'Walk-in').' '.($b->guest?->lname ?? '')),
                    'phone' => $b->phone ?: '-',
                    'status' => $statusText,
                    'stay_type' => $b->stay_type === 'short_stay' ? 'Short stay ('.$b->short_stay_hours.' hrs)' : '24 hours'.($b->with_breakfast ? ' with breakfast' : ''),
                    'checkin' => $b->check_in_datetime ? SystemFormatService::formatDateTime($b->check_in_datetime) : 'N/A',
                    'checkout' => $b->check_out_datetime ? SystemFormatService::formatDateTime($b->check_out_datetime) : 'N/A',
                    'room_numbers' => $roomNumbersStr,
                    'room_rate' => $roomRate,
                    'nights' => $nights,
                    'total_room_charges' => $totalRoomCharges,
                    'room_charges_db' => $roomChargesFromDB,
                    'amenities_list' => $amenitiesList,
                    'amenities_summary' => $amenitiesSummaryStr,
                    'total_amenity_charges' => $totalAmenityCharges,
                    'additional_charges_list' => $additionalChargesList,
                    'additional_charges_summary' => $additionalChargesSummaryStr,
                    'total_additional_charges' => $totalAdditionalCharges,
                    'discount_label' => $discountLabel,
                    'discount_amount' => $discountAmount,
                    'amount_paid' => $amountPaid,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethodFormatted,
                    'ewallet_ref' => $ewalletRefFormatted,
                    'is_paid' => $isPaid,
                ];
            }

            fputcsv($handle, ['SUMMARY METRICS', 'VALUE']);
            fputcsv($handle, ['Total Bookings', count($processedBookings)]);
            fputcsv($handle, ['Completed Bookings', $completedCount]);
            fputcsv($handle, ['In-House Bookings', $inHouseCount]);
            fputcsv($handle, ['Overdue Bookings', $overdueCount]);
            fputcsv($handle, ['Cancelled Bookings', $cancelledCount]);
            fputcsv($handle, ['Total Room Charges (PHP)', number_format($totalRoomRevenue, 2)]);
            fputcsv($handle, ['Total Amenity Charges (PHP)', number_format($totalAmenitiesRevenue, 2)]);
            fputcsv($handle, ['Total Additional Charges (PHP)', number_format($totalAdditionalChargesRevenue, 2)]);
            fputcsv($handle, ['Total Discounts Applied (PHP)', number_format($totalDiscountsApplied, 2)]);
            fputcsv($handle, ['Total Revenue / Amount Paid (PHP)', number_format($totalRevenue, 2)]);
            fputcsv($handle, []);

            // SECTION 1: BOOKING SUMMARY & PAYMENT OVERVIEW
            fputcsv($handle, ['--- BOOKING SUMMARY & PAYMENT OVERVIEW ---']);
            fputcsv($handle, [
                'Booking No.',
                'Guest First Name',
                'Guest Last Name',
                'Phone',
                'Status',
                'Stay Type',
                'Check-in',
                'Check-out',
                'Room Number(s)',
                'Room Rate (PHP)',
                'Number of Nights',
                'Total Room Charges (PHP)',
                'Purchased Amenities (Item, Qty, Unit Price, Total)',
                'Total Amenity Charges (PHP)',
                'Additional Charges / Extra Fees',
                'Total Additional Charges (PHP)',
                'Applied Discount',
                'Discount Amount (PHP)',
                'Total Amount Paid (PHP)',
                'Payment Date',
                'Payment Method',
                'E-Wallet Reference Number',
            ]);

            if (empty($processedBookings)) {
                fputcsv($handle, ['No booking records found within the selected date range.']);
            } else {
                foreach ($processedBookings as $pb) {
                    fputcsv($handle, [
                        $pb['booking_no'],
                        $pb['first_name'],
                        $pb['last_name'],
                        $pb['phone'],
                        $pb['status'],
                        $pb['stay_type'],
                        $pb['checkin'],
                        $pb['checkout'],
                        $pb['room_numbers'],
                        number_format($pb['room_rate'], 2),
                        $pb['nights'],
                        number_format($pb['total_room_charges'], 2),
                        $pb['amenities_summary'],
                        number_format($pb['total_amenity_charges'], 2),
                        $pb['additional_charges_summary'],
                        number_format($pb['total_additional_charges'], 2),
                        $pb['discount_label'],
                        number_format($pb['discount_amount'], 2),
                        number_format($pb['amount_paid'], 2),
                        $pb['payment_date'],
                        $pb['payment_method'],
                        $pb['ewallet_ref'],
                    ]);
                }
            }

            fputcsv($handle, []);

            // SECTION 2: ITEMIZED CHARGES & BILLING BREAKDOWN (ALL PAID & BILLED ITEMS)
            fputcsv($handle, ['--- ITEMIZED CHARGES & BILLING BREAKDOWN (ALL PAID & BILLED ITEMS) ---']);
            fputcsv($handle, [
                'Booking No.',
                'Guest Name',
                'Charge Category',
                'Item / Charge Description',
                'Room Number',
                'Quantity / Nights',
                'Unit Price (PHP)',
                'Total Amount (PHP)',
                'Payment Date',
                'Payment Method',
                'E-Wallet Reference Number',
            ]);

            if (empty($processedBookings)) {
                fputcsv($handle, ['No itemized records available.']);
            } else {
                foreach ($processedBookings as $pb) {
                    // Room Charge Line(s)
                    if ($pb['room_charges_db']->isNotEmpty()) {
                        foreach ($pb['room_charges_db'] as $rc) {
                            fputcsv($handle, [
                                $pb['booking_no'],
                                $pb['full_name'],
                                'Room Charge',
                                $rc->description,
                                $pb['room_numbers'],
                                $pb['nights'],
                                number_format((float) $rc->unit_price, 2),
                                number_format((float) $rc->amount, 2),
                                $pb['payment_date'],
                                $pb['payment_method'],
                                $pb['ewallet_ref'],
                            ]);
                        }
                    } elseif ($pb['total_room_charges'] > 0) {
                        fputcsv($handle, [
                            $pb['booking_no'],
                            $pb['full_name'],
                            'Room Charge',
                            'Room Accommodation ('.$pb['stay_type'].')',
                            $pb['room_numbers'],
                            $pb['nights'],
                            number_format($pb['room_rate'], 2),
                            number_format($pb['total_room_charges'], 2),
                            $pb['payment_date'],
                            $pb['payment_method'],
                            $pb['ewallet_ref'],
                        ]);
                    }

                    // Amenity Charge Line(s)
                    foreach ($pb['amenities_list'] as $amenityItem) {
                        fputcsv($handle, [
                            $pb['booking_no'],
                            $pb['full_name'],
                            'Amenity',
                            $amenityItem['name'],
                            $pb['room_numbers'],
                            $amenityItem['quantity'],
                            number_format($amenityItem['unit_price'], 2),
                            number_format($amenityItem['total_amount'], 2),
                            $pb['payment_date'],
                            $pb['payment_method'],
                            $pb['ewallet_ref'],
                        ]);
                    }

                    // Additional Charges Line(s)
                    foreach ($pb['additional_charges_list'] as $extraItem) {
                        fputcsv($handle, [
                            $pb['booking_no'],
                            $pb['full_name'],
                            'Additional Charge',
                            $extraItem['name'],
                            $pb['room_numbers'],
                            $extraItem['quantity'],
                            number_format($extraItem['unit_price'], 2),
                            number_format($extraItem['total_amount'], 2),
                            $pb['payment_date'],
                            $pb['payment_method'],
                            $pb['ewallet_ref'],
                        ]);
                    }

                    // Discount Line (if any)
                    if ($pb['discount_amount'] > 0) {
                        fputcsv($handle, [
                            $pb['booking_no'],
                            $pb['full_name'],
                            'Discount',
                            $pb['discount_label'].' Discount',
                            $pb['room_numbers'],
                            1,
                            '-'.number_format($pb['discount_amount'], 2),
                            '-'.number_format($pb['discount_amount'], 2),
                            $pb['payment_date'],
                            $pb['payment_method'],
                            $pb['ewallet_ref'],
                        ]);
                    }

                    // Payment Settlement Summary Line
                    fputcsv($handle, [
                        $pb['booking_no'],
                        $pb['full_name'],
                        'Payment',
                        $pb['is_paid'] ? 'Total Amount Paid (Settled)' : 'Total Amount Paid / Received',
                        $pb['room_numbers'],
                        1,
                        number_format($pb['amount_paid'], 2),
                        number_format($pb['amount_paid'], 2),
                        $pb['payment_date'],
                        $pb['payment_method'],
                        $pb['ewallet_ref'],
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
