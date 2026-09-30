<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\ReserveController; Turns to Booking instead
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BillingController;

// Gets
// Redirect root "/" to the First page
Route::get('/', function () {
    return redirect('/dashboard');
});

// Show the Login page 
Route::get('/login',function(){
    return view('content.login');
});

// Dashboard 
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

//Bookings Records View
Route::get('/bookings', [BookingController::class, 'index'])->name('booking.index');

//Post Route for BookingController
Route::post('/bookings', [BookingController::class, 'store'])->name('booking.store');

// Update Booking Status Route 
Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus'])->name('booking.updateStatus');

// Guest Records
Route::get('/guest',function(){
    return view('content.guestlist');
})->name('guest.index');

// Billing Records
Route::get('/billing', [BillingController::class, 'index'])->name('content.billing');
Route::post('/billing/{invoice_id}/add-amenity', [BillingController::class, 'addAmenity'])->name('billing.addAmenity');
Route::post('/billing/{invoicce_id}/process-payment', [BillingController::class, 'processPayment'])->name('billing.processPayment');



// Posts
// Route::post('/reservation', function(Request $request){

//     // Secured Validate
//     $request->validate([
//         'fname' => 'required|min:5|max:30',
//         'lname' => 'required|min:5|max:30',
//         'phone' => 'required|min:11|max:11',
//     ]);
//     // Getting Input
//     $fName = $request->input('fname');
//     $lName = $request->input('lname');
//     $phone = $request->input('phone');
// });
