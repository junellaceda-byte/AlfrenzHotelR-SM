<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Models\Staff;
use App\Services\ActivityLogService;
use App\Services\InvoiceService;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class BillingController
{
    //
    public function index(Request $request)
    {
        $pendingPage = $request->input('pending_page', $request->input('page', 1));
        $confirmedPage = $request->input('confirmed_page', $request->input('page', 1));

        // This is the logic for pending payments
        $pendingPayments = Invoice::where('status', 'unpaid')
            ->with(['booking.guest'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(9, ['*'], 'pending_page', $pendingPage)
            ->appends($request->except('edit_id'));

        // This is the logic for confirmed payments
        $confirmedPayments = Invoice::where('status', 'paid')
            ->with(['booking.guest', 'booking.charges', 'paymentLogs'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(9, ['*'], 'confirmed_page', $confirmedPage)
            ->appends($request->except('edit_id'));

        // This is the logic for selected invoice
        $selectedInvoice = null;
        if ($request->has('edit_id') && ! $request->has('confirmed_page')) {
            $selectedInvoice = Invoice::with(['booking.guest', 'booking.charges', 'availableAmenities'])
                ->find($request->edit_id);
        }

        // This is the logic for available amenities
        $availableAmenities = Amenity::where('offered', true)->get();

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

        return view('Receptionist.content.billing', compact('pendingPayments', 'confirmedPayments', 'selectedInvoice', 'availableAmenities', 'dueGuests', 'occupiedRoomsList'));
    }

    // This is the logic for adding amenities
    public function addAmenity(Request $request, $invoice_id)
    {
        $invoice = Invoice::with('booking')->findOrFail($invoice_id);

        AvailableAmenity::create([
            'booking_id' => $invoice->booking_id,
            'invoice_id' => $invoice->id,
            'item_name' => $request->item_name,
            'quantity' => 1,
            'unit_price' => $request->price,
            'total_amount' => $request->price,
            'is_available' => true,
        ]);

        if ($invoice->booking_id) {
            Charge::create([
                'booking_id' => $invoice->booking_id,
                'type' => 'amenity',
                'description' => $request->item_name,
                'quantity' => 1,
                'unit_price' => $request->price,
                'amount' => $request->price,
                'is_paid' => false,
            ]);

            app(InvoiceService::class)->recalculate($invoice->booking);
        } else {
            $invoice->increment('extra_charges_total', $request->price);
            $invoice->increment('grand_total', $request->price);
        }

        return redirect()->route('content.billing', ['edit_id' => $invoice->id])
            ->with('success', 'Amenity added successfully.');
    }

    public function removeAmenity(Request $request, $invoice_id)
    {
        $invoice = Invoice::with('booking')->findOrFail($invoice_id);

        $amenity = AvailableAmenity::where('invoice_id', $invoice->id)
            ->where('item_name', $request->item_name)
            ->latest()
            ->first();

        if ($amenity) {
            $price = $amenity->unit_price;
            $amenity->delete();

            if ($invoice->booking_id) {
                Charge::where('booking_id', $invoice->booking_id)
                    ->where('type', 'amenity')
                    ->where('description', $request->item_name)
                    ->latest()
                    ->first()
                    ?->delete();

                app(InvoiceService::class)->recalculate($invoice->booking);
            } else {
                $invoice->decrement('extra_charges_total', $price);
                $invoice->decrement('grand_total', $price);
            }
        }

        return redirect()->route('content.billing', ['edit_id' => $invoice->id])
            ->with('success', 'Amenity removed successfully.');
    }

    // This is the logic for processing payment
    public function processPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::with('booking')->findOrFail($invoice_id);

        $paymentMethod = $request->input('payment_method', 'cash');
        if (in_array(strtolower(trim((string) $paymentMethod)), ['ewallet', 'gcash', 'e-wallet', 'paymaya', 'paypal'])) {
            $paymentMethod = 'ewallet';
        }

        $referenceNumber = ($paymentMethod === 'ewallet')
            ? $request->input('reference_number')
            : null;

        $staffId = ActivityLogService::currentStaffId();
        $processedBy = null;
        if ($staffId) {
            $staff = Staff::find($staffId);
            $processedBy = $staff ? trim($staff->first_name.' '.$staff->last_name) : null;
        }

        // Record transaction log with fallback for un-migrated legacy databases with CHECK constraints
        try {
            $paymentLog = PaymentLog::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->grand_total,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'status' => 'successful',
                'paid_at' => now(),
                'processed_by' => $processedBy,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'CHECK constraint failed')) {
                $paymentLog = PaymentLog::create([
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $invoice->grand_total,
                    'payment_method' => 'gcash',
                    'reference_number' => $referenceNumber,
                    'status' => 'successful',
                    'paid_at' => now(),
                    'processed_by' => $processedBy,
                    'created_at' => now(),
                ]);
            } else {
                throw $e;
            }
        }

        // Record Payment Activity Log
        ActivityLogService::logPayment($paymentLog, $invoice, $staffId);

        // Change status to paid (moves it automatically to Confirmed Payment table)
        $invoice->status = 'paid';
        $invoice->updated_at = now();
        $invoice->save();

        // Check out the booking and free up the rooms
        if ($invoice->booking) {
            $invoice->booking->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
            ]);
            if (! empty($invoice->booking->booked_rooms)) {
                Room::whereIn('id', $invoice->booking->booked_rooms)->update(['status' => 'available']);
            }
        }

        return redirect()->route('content.billing')
            ->with('success', 'Payment processed! Moved to Confirmed Payments.');
    }
}
