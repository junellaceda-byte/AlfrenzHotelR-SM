<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guest;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;

class BookingController 
{
    //Validated Form Data
    public function index()
    {
        $bookings = Booking::with('guest', 'room')->get();
        return view('content.booking', compact('bookings'));
    }
    public function store (Request $request)
    {
        $request->validate([
            'fname' => 'required|string|min:4|max:30',
            'lname' => 'required|string|min:5|max:30',
            'phone' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:11',
            'room_id' => 'required|integer|exists:rooms,id',
            'stay_type' => 'required|in:short_stay,overnight',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'action_type' =>'required|in:reserved,check_in',
        ]);
        $guest = Guest::create([
            'first_name' => $request->filled('fname') ? $request->fname : 'Walk-In',
            'last_name'  => $request->filled('lname') ? $request->lname : 'Guest #' . rand(100, 999),
            'phone_number' => $request->phone,
        ]);
        
        //Finds the room based on the room_id from the request
        $room = Room::findOrFail($request->room_id);
        if ($request->stay_type ==='short_stay') {
            $roomRate = $room->short_stay_rate;
        } else {
            $checkIn=$request->date('check_in');
            $checkOut=$request->date('check_out');
            $nights = $checkIn->diffInDays($checkOut)?:1;

            $roomRate = $nights * $room->overnight_rate;
        }

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'stay_type' => $request->stay_type,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'status' => $request->action_type === 'reserved' ? 'reserved' : 'checked_in',
        ]);

        if ($request->action_type === 'check_in') {
            $room->update(['status' => 'occupied']);
        }
        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'room_charges' => $roomRate,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => $roomRate,
            'status' => 'unpaid',
            // 'due_date' => now()->addDays(7), Optional
        ]);
        return redirect()->back()->with('success', 'Booking created & recorded successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
       $booking = Booking::findOrFail($id);
       $booking->update(['status' => $request->new_status]);

       if ($request->new_status === 'checked_in'){
        $booking->room->update(['status' => 'occupied']);
       }
       else if ($request->new_status === 'checked_out'){
        $booking->room->update(['status' => 'available']);
       }
        return redirect()->back()->with('success', 'Booking status updated!');
    }
}