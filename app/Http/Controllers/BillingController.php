<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoices;
use App\Models\ExtraCharge;
use App\Models\PaymentLog;

class BillingController 
{
    //
    public function index(Request $request)
    {
        $pendingPayments = Invoices::where('status', 'unpaid')
            ->with(['booking.guest', 'booking.room'])
            ->get();

        $confirmedPayments = Invoices::where('status', 'paid')
            ->with(['booking.guest', 'booking.room', 'paymentLogs'])
            ->get();
        $selectedInvoice = null;
        if ($request->has('edit_id')) {
            $selectedInvoice = Invoices::with(['booking.guest', 'booking.room', 'extraCharges'])
                ->find($request->edit_id);  
        }
        return view('content.billing', compact('pendingPayments', 'confirmedPayments', 'selectedInvoice'));
    }

    public function addAmenity(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        ExtraCharge::create([
            'booking_id' => $invoice->booking_id,
            'item_name' => $request->item_name,
            'quantity' => 1,
            'unit_price' => $request->price,
            'total_amount' => $request->price
        ]);
        $invoice->increment('extra_charges_total', $request->price);
        $invoice->increment('grand_total', $request->price);

        return redirect()->route('content.billing', ['edit_id' => $invoice->id])
            ->with('success', 'Amenity added successfully.');
    }

    public function processPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        // Record transaction log 
        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => $invoice->grand_total,
            'payment_method' => $request->payment_method,
            'status' => 'successful',
            'created_at' => now()
        ]);

        // Change status to paid (moves it automatically to Confirmed Payment table)
        $invoice->update(['status' => 'paid']);

        return redirect()->route('content.billing')
            ->with('success', 'Payment processed! Moved to Confirmed Payments.');
    }
}
