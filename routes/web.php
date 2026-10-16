<?php

use App\Http\Controllers\BillingController;
// use App\Http\Controllers\ReserveController; Turns to Booking instead
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Gets
// Redirect root "/" to the First page
Route::get('/', function () {
    return redirect('/dashboard');
});

// Show the Login page
Route::get('/login', function () {
    return view('content.login');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Bookings Records View
Route::get('/bookings', [BookingController::class, 'index'])->name('booking.index');

// Post Route for BookingController
Route::post('/bookings', [BookingController::class, 'store'])->name('booking.store');

// Update Booking Status Route
Route::put('/bookings/{id}/status', [BookingController::class, 'updateStatus'])->name('booking.updateStatus');

// Guest Records
// Route::get('/guest', function () {
//     return view('content.guestlist');
// })->name('guest.index');

// Billing Records
Route::get('/billing', [BillingController::class, 'index'])->name('content.billing');
Route::post('/billing/{invoice_id}/add-amenity', [BillingController::class, 'addAmenity'])->name('billing.addAmenity');
Route::post('/billing/{invoice_id}/remove-amenity', [BillingController::class, 'removeAmenity'])->name('billing.removeAmenity');
Route::post('/billing/{invoice_id}/process-payment', [BillingController::class, 'processPayment'])->name('billing.processPayment');

// Posts
Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');

Route::get('/reservation', [ReservationController::class, 'index'])->name('reservation.index');
Route::get('/api/available-rooms', [ReservationController::class, 'getAvailableRooms'])->name('api.available-rooms');

// Reservation edit & delete
Route::put('/reservation/{id}', [ReservationController::class, 'update'])->name('reservation.update');
Route::delete('/reservation/{id}', [ReservationController::class, 'destroy'])->name('reservation.destroy');

// Booking edit & delete
Route::put('/bookings/{id}', [BookingController::class, 'update'])->name('booking.update');
Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
