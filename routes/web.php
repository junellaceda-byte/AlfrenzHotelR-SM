<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
// use App\Http\Controllers\ReserveController; Turns to Booking instead
use App\Http\Controllers\RoomBlockController;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemFormatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// ---------------------------------------------------
// Administrator & Login Routes
// ---------------------------------------------------

Route::get('/', function () {
    return view('welcome');
});

// GET Route to view the Login Page
Route::get('/login', function () {
    // Looks inside resources/views/LogIn/ for login.blade.php
    return view('LogIn.login');
});

Route::get('/admindashboard', function (Request $request) {
    // Get the current section from the URL, default to 'overview'
    $section = $request->query('section', 'overview');

    // Fetch all staff from the database
    $staffs = Staff::all();

    // Fetch real-time room data from database for Room Management section
    $roomData = DashboardController::getRoomsData();
    $rooms = $roomData['rooms'];
    $availableRooms = $roomData['availableRooms'];
    $occupiedRooms = $roomData['occupiedRooms'];
    $reservedRooms = $roomData['reservedRooms'];
    $underMaintenanceRooms = $roomData['underMaintenanceRooms'];
    $activeBlocks = $roomData['activeBlocks'];

    // --- Overview card data (today only, Asia/Manila timezone) ---
    $today = now()->setTimezone('Asia/Manila')->toDateString();

    // 1. Today's Occupancy: count of distinct rooms occupied or booked today
    $todayBookings = Booking::where('status', '!=', 'cancelled')
        ->where(function ($q) use ($today) {
            $q->where('status', 'checked_in')
                ->orWhereDate('check_in_datetime', $today)
                ->orWhereDate('created_at', $today);
        })
        ->get();

    $occupiedRoomIds = collect();
    foreach ($todayBookings as $booking) {
        $bookingRooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : json_decode($booking->booked_rooms, true);
        if (! empty($bookingRooms)) {
            foreach ($bookingRooms as $rId) {
                $occupiedRoomIds->push((int) $rId);
            }
        }
    }
    $occupancyToday = $occupiedRoomIds->unique()->count();
    $totalRooms = Room::count();

    // 2. Revenue Today: total amount of money actually collected from guests today
    $revenueToday = (float) PaymentLog::where('status', 'successful')
        ->where(function ($q) use ($today) {
            $q->whereDate('paid_at', $today)
                ->orWhereDate('created_at', $today);
        })
        ->sum('amount_paid');

    // 3. Check-ins Today: all guests who actually checked in today (excluding reservations still marked 'reserved')
    $todayCheckIns = Booking::with('guest')
        ->where(function ($q) use ($today) {
            $q->whereDate('check_in_datetime', $today)
                ->orWhereDate('created_at', $today);
        })
        ->whereIn('status', ['checked_in', 'checked_out'])
        ->orderByDesc('check_in_datetime')
        ->get();

    $checkInsToday = $todayCheckIns->count();

    // 4. Check-outs Today: total guests who have checked out of their occupied rooms today
    $todayCheckOuts = Booking::with('guest')
        ->where('status', 'checked_out')
        ->where(function ($q) use ($today) {
            $q->whereDate('actual_check_out', $today)
                ->orWhere(function ($sub) use ($today) {
                    $sub->whereNull('actual_check_out')
                        ->where(function ($dt) use ($today) {
                            $dt->whereDate('check_out_datetime', $today)
                                ->orWhereDate('updated_at', $today);
                        });
                });
        })
        ->orderByDesc('actual_check_out')
        ->get();

    $checkOutsToday = $todayCheckOuts->count();

    // Fetch all amenities from the database for the Amenities section
    $amenities = Amenity::orderBy('id', 'asc')->get();

    // Administrator user record for Settings
    $adminUser = User::first() ?? new User(['username' => 'admin']);

    // Pass the variables to the admindash layout
    return view('Administrator.layout.admindash', compact(
        'section',
        'staffs',
        'amenities',
        'occupancyToday',
        'revenueToday',
        'checkInsToday',
        'checkOutsToday',
        'totalRooms',
        'todayCheckIns',
        'todayCheckOuts',
        'rooms',
        'availableRooms',
        'occupiedRooms',
        'reservedRooms',
        'underMaintenanceRooms',
        'activeBlocks',
        'adminUser'
    ));
});

// Admin Room Management Routes
Route::post('/admin/rooms/store', function (Request $request) {
    $validated = $request->validate([
        'room_number' => 'required|string|max:50|unique:rooms,room_number',
        'short_stay_rate' => 'required|numeric|min:0',
        'overnight_rate' => 'required|numeric|min:0',
    ]);

    Room::create([
        'room_number' => $validated['room_number'],
        'short_stay_rate' => (float) $validated['short_stay_rate'],
        'overnight_rate' => (float) $validated['overnight_rate'],
        'status' => 'available',
    ]);

    return redirect('/admindashboard?section=rooms')->with('success', 'Room '.$validated['room_number'].' created successfully.');
})->name('admin.rooms.store');

Route::put('/admin/rooms/{id}/update', function (Request $request, $id) {
    $validated = $request->validate([
        'room_number' => 'required|string|max:50|unique:rooms,room_number,'.$id,
        'short_stay_rate' => 'required|numeric|min:0',
        'overnight_rate' => 'required|numeric|min:0',
        'status' => 'nullable|in:available,occupied,under_maintenance,reserved',
    ]);

    $room = Room::findOrFail($id);
    $room->update([
        'room_number' => $validated['room_number'],
        'short_stay_rate' => (float) $validated['short_stay_rate'],
        'overnight_rate' => (float) $validated['overnight_rate'],
        'status' => $validated['status'] ?? $room->status,
    ]);

    return redirect('/admindashboard?section=rooms')->with('success', 'Room '.$room->room_number.' updated successfully.');
})->name('admin.rooms.update');

Route::delete('/admin/rooms/{id}/delete', function ($id) {
    $room = Room::findOrFail($id);

    // Prevent deletion if room has active bookings or reservations
    $hasActiveBooking = Booking::whereNotIn('status', ['cancelled', 'checked_out'])
        ->where(function ($q) use ($room) {
            $q->whereJsonContains('booked_rooms', (string) $room->id)
                ->orWhereJsonContains('booked_rooms', (int) $room->id);
        })->exists();

    $hasActiveReservation = Reservation::whereNotIn('status', ['cancelled', 'checked_in'])
        ->where(function ($q) use ($room) {
            $q->whereJsonContains('selected_rooms', (string) $room->id)
                ->orWhereJsonContains('selected_rooms', (int) $room->id);
        })->exists();

    if ($hasActiveBooking || $hasActiveReservation) {
        return redirect('/admindashboard?section=rooms')->with('error', 'Cannot delete Room '.$room->room_number.' because it is currently occupied or reserved.');
    }

    $roomNumber = $room->room_number;
    $room->delete();

    return redirect('/admindashboard?section=rooms')->with('success', 'Room '.$roomNumber.' deleted successfully.');
})->name('admin.rooms.destroy');

// Admin Settings Routes (Credentials & Timestamp formats)
Route::put('/admin/settings', function (Request $request) {
    $validated = $request->validate([
        'username' => ['required', 'string', 'max:255'],
        'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        'date_format' => ['required', 'string', Rule::in(['M d, Y', 'F d, Y', 'F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'])],
        'time_format' => ['required', 'string', Rule::in(['h:i A', 'h:i:s A', 'H:i', 'H:i:s'])],
    ]);

    // Update system-wide timestamp display formats
    SystemSetting::set('date_format', $validated['date_format']);
    SystemSetting::set('time_format', $validated['time_format']);

    // Update Administrator credentials
    $admin = User::first() ?? new User;
    $admin->username = $validated['username'];
    $admin->user_id = $validated['username'];

    if (! empty($validated['password'])) {
        $admin->password = Hash::make($validated['password']);
    } elseif (! $admin->exists) {
        $admin->password = Hash::make('admin123');
    }

    $admin->save();

    return redirect('/admindashboard?section=settings')->with('success', 'System settings and administrator credentials updated successfully.');
})->name('admin.settings.update');

Route::get('/api/system/settings', function () {
    $dateFormat = SystemFormatService::dateFormat();
    $timeFormat = SystemFormatService::timeFormat();
    $now = now()->setTimezone('Asia/Manila');

    return response()->json([
        'date_format' => $dateFormat,
        'time_format' => $timeFormat,
        'formatted_date' => $now->format($dateFormat),
        'formatted_time' => $now->format($timeFormat),
        'server_time' => $now->toIso8601String(),
    ]);
})->name('api.system.settings');

Route::post('/amenities/store', function (Request $request) {
    // 1. Validate the incoming data from the form
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric',
        'charged' => 'required|in:person,item,hours,night',
    ]);

    // 2. Set default offered status to true
    $validated['offered'] = true;

    // 3. Save the new amenity into the database
    $amenity = Amenity::create($validated);

    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity created successfully.',
            'amenity' => $amenity,
        ]);
    }

    // 4. Redirect the user straight back to the Amenities tab
    return redirect('/admindashboard?section=amenity')->with('success', 'Amenity created successfully.');
});

// Route to Update an Amenity
Route::put('/amenities/{id}/update', function (Request $request, $id) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric',
        'charged' => 'required|in:person,item,hours,night',
        'offered' => 'required|boolean',
    ]);

    $amenity = Amenity::findOrFail($id);
    $amenity->update($validated);

    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity updated successfully.',
            'amenity' => $amenity,
        ]);
    }

    return redirect('/admindashboard?section=amenity')->with('success', 'Amenity updated successfully.');
});

// Route to Toggle Amenity Status (Offered / Not Offered)
Route::post('/amenities/{id}/toggle', function (Request $request, $id) {
    $amenity = Amenity::findOrFail($id);
    $amenity->offered = ! $amenity->offered;
    $amenity->save();

    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity status updated successfully.',
            'amenity' => $amenity,
            'offered' => (bool) $amenity->offered,
        ]);
    }

    return redirect('/admindashboard?section=amenity')->with('success', 'Amenity status updated successfully.');
})->name('amenities.toggle');

// Route to Delete an Amenity
Route::delete('/amenities/{id}/delete', function (Request $request, $id) {
    $amenity = Amenity::findOrFail($id);
    $amenity->delete();

    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity deleted successfully.',
        ]);
    }

    return redirect('/admindashboard?section=amenity')->with('success', 'Amenity deleted successfully.');
});

Route::post('/staff/store', function (Request $request) {
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gmail' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:255',
        'username' => 'required|string|max:255|unique:staff',
        'password' => 'required|string|min:6',
    ]);

    Staff::create($validated);

    return redirect('/admindashboard?section=staff');
});

// Route to Update Staff
Route::put('/staff/{id}/update', function (Request $request, $id) {
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gmail' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:255',
        // The unique rule ignores the current staff ID so they can keep their existing username
        'username' => 'required|string|max:255|unique:staff,username,'.$id,
        'password' => 'required|string|min:6',
    ]);

    $staff = Staff::findOrFail($id);
    $staff->update($validated);

    return redirect('/admindashboard?section=staff');
});

// Route to Delete Staff
Route::delete('/staff/{id}/delete', function ($id) {
    $staff = Staff::findOrFail($id);
    $staff->delete();

    return redirect('/admindashboard?section=staff');
});

// ---------------------------------------------------
// Receptionist Dashboard Routes (From Second File)
// ---------------------------------------------------

// Gets
// Redirect root "/" to the First page
Route::get('/', function () {
    return redirect('/dashboardreceptionist');
});

// Dashboard
Route::get('/dashboardreceptionist', [DashboardController::class, 'index'])->name('dashboard.index');

// Bookings Records View
Route::get('/bookings', [BookingController::class, 'index'])->name('booking.index');

// Post Route for BookingController
Route::post('/bookings', [BookingController::class, 'store'])->name('booking.store');
Route::post('/bookings/amenities/add-to-room', [BookingController::class, 'addAmenityToRoom'])->name('booking.addAmenityToRoom');
Route::post('/bookings/{id}/amenities', [BookingController::class, 'addAmenities'])->name('booking.addAmenities');

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

// Reservation Routes
// Post Route for ReservationController
Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
Route::post('/reservation/{id}/check-in', [ReservationController::class, 'checkIn'])->name('reservation.checkIn');
Route::post('/reservation/{id}/cancel', [ReservationController::class, 'cancel'])->name('reservation.cancel');

// Room Block Routes
Route::post('/room-blocks', [RoomBlockController::class, 'store'])->name('room-block.store');
Route::post('/room-blocks/{id}/remove', [RoomBlockController::class, 'destroy'])->name('room-block.destroy');

// Get Routes for ReservationController
Route::get('/reservation', [ReservationController::class, 'index'])->name('reservation.index');
Route::get('/api/available-rooms', [ReservationController::class, 'getAvailableRooms'])->name('api.available-rooms');

// Real-time dashboard polling endpoint: notifications + KPI counts + auto-checkout of overdue rooms
Route::get('/api/dashboard/notifications', [DashboardApiController::class, 'notifications'])->name('api.dashboard.notifications');
Route::get('/api/admin/overview-stats', [DashboardApiController::class, 'adminOverviewStats'])->name('api.admin.overview-stats');
Route::get('/api/admin/revenue-stats', [DashboardApiController::class, 'revenueStats'])->name('api.admin.revenue-stats');
Route::get('/api/admin/notifications', [DashboardApiController::class, 'adminNotifications'])->name('api.admin.notifications');
Route::get('/api/admin/sales-report', [DashboardApiController::class, 'salesReport'])->name('api.admin.sales-report');
Route::post('/api/admin/monthly-target', [DashboardApiController::class, 'saveMonthlyTarget'])->name('api.admin.monthly-target');
Route::get('/api/amenities', [DashboardApiController::class, 'amenities'])->name('api.amenities');

// Reservation edit & delete
Route::put('/reservation/{id}', [ReservationController::class, 'update'])->name('reservation.update');
Route::delete('/reservation/{id}', [ReservationController::class, 'destroy'])->name('reservation.destroy');

// Booking edit & delete
Route::put('/bookings/{id}', [BookingController::class, 'update'])->name('booking.update');
Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
