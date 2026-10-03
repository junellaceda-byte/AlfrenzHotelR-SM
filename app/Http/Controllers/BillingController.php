<?php

namespace App\Http\Controllers;

use App\Models\AvailableAmenity;
use App\Models\Invoice;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class BillingController
{
    //
    public function index(Request $request)
    {
        // This is the logic for pending payments
        $pendingPayments = Invoice::where('status', 'unpaid')
            ->with(['booking.guest'])
            ->get();

        // This is the logic for confirmed payments
        $confirmedPayments = Invoice::where('status', 'paid')
            ->with(['booking.guest', 'paymentLogs'])
            ->get();

        // This is the logic for selected invoice
        $selectedInvoice = null;
        if ($request->has('edit_id')) {
            $selectedInvoice = Invoice::with(['booking.guest', 'availableAmenities'])
                ->find($request->edit_id);
        }

        // This is the logic for available amenities
        $availableAmenities = AvailableAmenity::all();

        return view('content.billing', compact('pendingPayments', 'confirmedPayments', 'selectedInvoice', 'availableAmenities'));
    }

    // This is the logic for adding amenities
    public function addAmenity(Request $request, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        AvailableAmenity::create([
            'booking_id' => $invoice->booking_id,
            'invoice_id' => $invoice->id,
            'item_name' => $request->item_name,
            'quantity' => 1,
            'unit_price' => $request->price,
            'total_amount' => $request->price,
        ]);
        $invoice->increment('extra_charges_total', $request->price);
        $invoice->increment('grand_total', $request->price);

        return redirect()->route('content.billing', ['edit_id' => $invoice->id])
            ->with('success', 'Amenity added successfully.');
    }

    public function removeAmenity(Request $request, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        $amenity = AvailableAmenity::where('invoice_id', $invoice->id)
            ->where('item_name', $request->item_name)
            ->latest()
            ->first();

        if ($amenity) {
            $price = $amenity->unit_price;
            $amenity->delete();
            $invoice->decrement('extra_charges_total', $price);
            $invoice->decrement('grand_total', $price);
        }

        return redirect()->route('content.billing', ['edit_id' => $invoice->id])
            ->with('success', 'Amenity removed successfully.');
    }

    // This is the logic for processing payment
    public function processPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        // Record transaction log
        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => $invoice->grand_total,
            'payment_method' => $request->payment_method,
            'status' => 'successful',
            'created_at' => now(),
        ]);

        // Change status to paid (moves it automatically to Confirmed Payment table)
        $invoice->update(['status' => 'paid']);

        return redirect()->route('content.billing')
            ->with('success', 'Payment processed! Moved to Confirmed Payments.');
    }
}
