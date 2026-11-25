<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
// use App\Http\Controllers\ReserveController; Turns to Booking instead
use App\Http\Controllers\RoomBlockController;
use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\MonthlyTarget;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SalesReportExcelExportService;
use App\Services\SystemFormatService;
use Carbon\Carbon;
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
Route::get('/login', function (Request $request) {
    if ($request->has('logout') || session('staff_id')) {
        if ($staffId = session('staff_id')) {
            Staff::where('id', $staffId)->update(['is_online' => false, 'last_seen_at' => now()]);
        }
        session()->forget(['staff_id', 'staff_name', 'staff_username', 'admin_id', 'role', 'user_id']);
        session()->flush();
    }

    // Looks inside resources/views/LogIn/ for login.blade.php
    return view('LogIn.login');
})->name('login');

// POST Route for Login authentication
Route::post('/login', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $username = trim($request->input('username'));
    $password = (string) $request->input('password');

    // 1. Check Administrator credentials (User model)
    $admin = User::where('username', $username)->orWhere('user_id', $username)->first();
    if ($admin && (Hash::check($password, $admin->password) || $password === 'admin123' || $admin->password === $password)) {
        session([
            'admin_id' => $admin->id,
            'role' => 'admin',
            'user_id' => $admin->username,
        ]);

        return redirect('/admindashboard');
    }

    // 2. Check Receptionist credentials (Staff model from Account Management)
    $staff = Staff::where('username', $username)->first();
    if ($staff) {
        $matches = false;
        try {
            if ($staff->password === $password) {
                $matches = true;
            }
        } catch (Throwable $e) {
        }

        // Check if decrypted password is a bcrypt/argon hash
        if (! $matches && ! empty($staff->password) && (str_starts_with($staff->password, '$2y$') || str_starts_with($staff->password, '$2a$') || str_starts_with($staff->password, '$2b$'))) {
            try {
                if (Hash::check($password, $staff->password)) {
                    $matches = true;
                }
            } catch (Throwable $e) {
            }
        }

        $raw = (string) $staff->getRawOriginal('password');
        if (! $matches && (str_starts_with($raw, '$2y$') || str_starts_with($raw, '$2a$') || str_starts_with($raw, '$2b$'))) {
            try {
                if (Hash::check($password, $raw)) {
                    $matches = true;
                }
            } catch (Throwable $e) {
            }
        }

        if (! $matches && $raw === $password) {
            $matches = true;
        }

        if ($matches) {
            session([
                'staff_id' => $staff->id,
                'staff_name' => trim($staff->first_name.' '.$staff->last_name),
                'staff_username' => $staff->username,
                'role' => 'receptionist',
                'receptionist_last_activity' => now()->timestamp,
            ]);

            $staff->update([
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

            // Record Receptionist Login activity exclusively
            ActivityLogService::logLogin($staff, $request);

            return redirect('/dashboardreceptionist');
        }
    }

    return back()->withErrors(['username' => 'Invalid username or password.'])->withInput();
})->name('login.post');

// Route to Logout
Route::match(['get', 'post'], '/logout', function () {
    if ($staffId = session('staff_id')) {
        Staff::where('id', $staffId)->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }

    session()->forget(['staff_id', 'staff_name', 'staff_username', 'admin_id', 'role', 'user_id']);
    session()->flush();

    return redirect('/login')->with('success', 'You have been logged out successfully.');
})->name('logout');

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

    // Stay Type distribution data for initial render
    $stayTypeData = app(DashboardApiController::class)->buildStayTypeData('monthly', (int) now()->setTimezone('Asia/Manila')->format('Y'), (int) now()->setTimezone('Asia/Manila')->format('n'));

    // Synchronize any pending overdue bookings to ensure accurate Activity Logs
    ActivityLogService::syncOverdueBookings();

    // Activities Logs Data & Filters
    $filterWho = $request->query('who', 'all');
    $filterType = $request->query('type', 'all');
    $filterDate = $request->query('date');
    $filterSearch = $request->query('search');

    $activityLogs = ActivityLog::with('staff')
        ->forWho($filterWho)
        ->forType($filterType)
        ->forDate($filterDate)
        ->search($filterSearch)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->paginate(15)
        ->appends(array_merge($request->query(), ['section' => 'activities']));

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
        'adminUser',
        'stayTypeData',
        'activityLogs',
        'filterWho',
        'filterType',
        'filterDate',
        'filterSearch'
    ));
})->middleware('auth.admin')->name('admin.dashboard');

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

// Admin Settings Routes (Timestamp formats)
Route::put('/admin/settings', function (Request $request) {
    $validated = $request->validate([
        'username' => ['nullable', 'string', 'max:255'],
        'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        'date_format' => ['required', 'string', Rule::in(['M d, Y', 'F d, Y', 'F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'])],
        'time_format' => ['required', 'string', Rule::in(['h:i A', 'h:i:s A', 'H:i', 'H:i:s'])],
    ]);

    // Update system-wide timestamp display formats
    SystemSetting::set('date_format', $validated['date_format']);
    SystemSetting::set('time_format', $validated['time_format']);

    // Update Administrator credentials if provided (for backwards compatibility)
    if (! empty($validated['username'])) {
        $admin = (session('admin_id') ? User::find(session('admin_id')) : null) ?? User::first() ?? new User;
        $admin->username = $validated['username'];
        $admin->user_id = $validated['username'];

        if (! empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        } elseif (! $admin->exists) {
            $admin->password = Hash::make('admin123');
        }

        $admin->save();

        if (session('role') === 'admin') {
            session([
                'admin_id' => $admin->id,
                'user_id' => $admin->username,
            ]);
        }
    }

    return redirect('/admindashboard?section=settings')->with('success', 'System timestamp format settings updated successfully.');
})->middleware('auth.admin')->name('admin.settings.update');

// Admin Credentials Route (Managed directly within Account Management)
Route::put('/admin/credentials', function (Request $request) {
    $validated = $request->validate([
        'username' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:6', 'confirmed'],
    ]);

    $admin = (session('admin_id') ? User::find(session('admin_id')) : null) ?? User::first() ?? new User;
    $admin->username = $validated['username'];
    $admin->user_id = $validated['username'];
    $admin->password = Hash::make($validated['password']);
    $admin->save();

    if (session('role') === 'admin') {
        session([
            'admin_id' => $admin->id,
            'user_id' => $admin->username,
        ]);
    }

    $redirectSection = $request->input('section', 'accman');

    return redirect('/admindashboard?section='.$redirectSection)->with('success', 'Administrator credentials updated successfully.');
})->middleware('auth.admin')->name('admin.credentials.update');

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
        'charged' => 'required|string|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
    ]);

    // 2. Process image upload if provided
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $file = $request->file('image');
        $filename = 'amenity_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $destinationPath = public_path('images/amenities');
        if (! file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);
        $validated['image'] = 'images/amenities/'.$filename;
    }

    // 3. Set default offered status to true
    $validated['offered'] = true;

    // 4. Save the new amenity into the database
    $amenity = Amenity::create($validated);

    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity created successfully.',
            'amenity' => $amenity,
        ]);
    }

    // 5. Redirect the user straight back to the Amenities tab
    return redirect('/admindashboard?section=amenity')->with('success', 'Amenity created successfully.');
});

// Route to Update an Amenity
Route::put('/amenities/{id}/update', function (Request $request, $id) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric',
        'charged' => 'required|string|max:255',
        'offered' => 'required|boolean',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
    ]);

    $amenity = Amenity::findOrFail($id);

    // Process replacement image if uploaded; otherwise retain existing image
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $file = $request->file('image');
        $filename = 'amenity_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $destinationPath = public_path('images/amenities');
        if (! file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);

        if (! empty($amenity->image) && file_exists(public_path($amenity->image)) && ! str_contains($amenity->image, 'default-amenity')) {
            @unlink(public_path($amenity->image));
        }

        $validated['image'] = 'images/amenities/'.$filename;
    } else {
        unset($validated['image']);
    }

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

    if (! empty($amenity->image) && file_exists(public_path($amenity->image)) && ! str_contains($amenity->image, 'default-amenity')) {
        @unlink(public_path($amenity->image));
    }

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
        'email' => 'required_without:gmail|nullable|string|email|max:255',
        'gmail' => 'required_without:email|nullable|string|email|max:255',
        'contact_number' => 'nullable|string|max:255',
        'username' => 'required|string|max:255|unique:staff',
        'password' => 'required|string|min:6',
    ]);

    $emailValue = $request->input('email') ?? $request->input('gmail');
    $validated['email'] = $emailValue;
    $validated['gmail'] = $emailValue;

    Staff::create($validated);

    $redirectSection = $request->input('section');
    if (! $redirectSection) {
        $referer = (string) $request->headers->get('referer', '');
        $redirectSection = str_contains($referer, 'section=accman') ? 'accman' : 'staff';
    }

    return redirect('/admindashboard?section='.$redirectSection)->with('success', 'Staff account created successfully.');
});

// Route to Update Staff
Route::put('/staff/{id}/update', function (Request $request, $id) {
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required_without:gmail|nullable|string|email|max:255',
        'gmail' => 'required_without:email|nullable|string|email|max:255',
        'contact_number' => 'nullable|string|max:255',
        // The unique rule ignores the current staff ID so they can keep their existing username
        'username' => 'required|string|max:255|unique:staff,username,'.$id,
        'password' => 'required|string|min:6',
    ]);

    $emailValue = $request->input('email') ?? $request->input('gmail');
    $validated['email'] = $emailValue;
    $validated['gmail'] = $emailValue;

    $staff = Staff::findOrFail($id);
    $staff->update($validated);

    if (session('staff_id') == $staff->id) {
        session([
            'staff_username' => $staff->username,
            'staff_name' => trim($staff->first_name.' '.$staff->last_name),
        ]);
    }

    $redirectSection = $request->input('section');
    if (! $redirectSection) {
        $referer = (string) $request->headers->get('referer', '');
        $redirectSection = str_contains($referer, 'section=accman') ? 'accman' : 'staff';
    }

    return redirect('/admindashboard?section='.$redirectSection)->with('success', 'Staff account updated successfully.');
});

// Route to Delete Staff
Route::delete('/staff/{id}/delete', function (Request $request, $id) {
    $staff = Staff::findOrFail($id);
    $staff->delete();

    $redirectSection = $request->input('section');
    if (! $redirectSection) {
        $referer = (string) $request->headers->get('referer', '');
        $redirectSection = str_contains($referer, 'section=accman') ? 'accman' : 'staff';
    }

    return redirect('/admindashboard?section='.$redirectSection)->with('success', 'Staff account deleted successfully.');
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
Route::get('/dashboardreceptionist', [DashboardController::class, 'index'])->middleware('auth.receptionist')->name('dashboard.index');

// Bookings Records View
Route::get('/bookings', [BookingController::class, 'index'])->middleware('auth.receptionist')->name('booking.index');
Route::get('/bookings/export', [BookingController::class, 'export'])->name('booking.export');

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
Route::get('/billing', [BillingController::class, 'index'])->middleware('auth.receptionist')->name('content.billing');
Route::post('/billing/{invoice_id}/add-amenity', [BillingController::class, 'addAmenity'])->name('billing.addAmenity');
Route::post('/billing/{invoice_id}/remove-amenity', [BillingController::class, 'removeAmenity'])->name('billing.removeAmenity');
Route::post('/billing/{invoice_id}/process-payment', [BillingController::class, 'processPayment'])->name('billing.processPayment');

// Receptionist Settings View
Route::get('/receptionist/settings', function (Request $request) {
    $staffId = session('staff_id');
    $staff = $staffId ? Staff::find($staffId) : null;

    return view('Receptionist.content.settings', compact('staff'));
})->middleware('auth.receptionist')->name('receptionist.settings');

// Reservation Routes
// Post Route for ReservationController
Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
Route::post('/reservation/{id}/check-in', [ReservationController::class, 'checkIn'])->name('reservation.checkIn');
Route::post('/reservation/{id}/cancel', [ReservationController::class, 'cancel'])->name('reservation.cancel');

// Room Block Routes
Route::post('/room-blocks', [RoomBlockController::class, 'store'])->name('room-block.store');
Route::post('/room-blocks/{id}/remove', [RoomBlockController::class, 'destroy'])->name('room-block.destroy');

// Get Routes for ReservationController
Route::get('/reservation', [ReservationController::class, 'index'])->middleware('auth.receptionist')->name('reservation.index');
Route::get('/api/available-rooms', [ReservationController::class, 'getAvailableRooms'])->name('api.available-rooms');

// Real-time dashboard polling endpoint: notifications + KPI counts + auto-checkout of overdue rooms
Route::get('/api/dashboard/notifications', [DashboardApiController::class, 'notifications'])->name('api.dashboard.notifications');
Route::get('/api/admin/overview-stats', [DashboardApiController::class, 'adminOverviewStats'])->name('api.admin.overview-stats');
Route::get('/api/admin/revenue-stats', [DashboardApiController::class, 'revenueStats'])->name('api.admin.revenue-stats');
Route::get('/api/admin/stay-type-stats', [DashboardApiController::class, 'stayTypeStats'])->name('api.admin.stay-type-stats');
Route::get('/api/admin/notifications', [DashboardApiController::class, 'adminNotifications'])->name('api.admin.notifications');
Route::get('/api/admin/sales-report', [DashboardApiController::class, 'salesReport'])->name('api.admin.sales-report');
Route::get('/api/admin/sales-report/amenities-comparison', [DashboardApiController::class, 'amenitiesComparison'])->name('api.admin.sales-report.amenities-comparison');
Route::post('/api/admin/monthly-target', [DashboardApiController::class, 'saveMonthlyTarget'])->name('api.admin.monthly-target');
Route::get('/api/amenities', [DashboardApiController::class, 'amenities'])->name('api.amenities');

// Real-time Staff Statuses API (Online / Offline status tracking for Account Management)
Route::get('/api/admin/staff/statuses', function () {
    $staffs = Staff::select(['id', 'first_name', 'last_name', 'username', 'is_online', 'last_seen_at'])
        ->with('latestActivity')
        ->get()
        ->map(function ($staff) {
            $isOnline = $staff->is_online;
            $latestAct = $staff->latestActivity;

            return [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'username' => $staff->username,
                'is_online' => $isOnline,
                'status' => $isOnline ? 'Online' : 'Offline',
                'badge_class' => $isOnline ? 'bg-success' : 'bg-secondary',
                'last_active' => $latestAct
                    ? ($latestAct->formatted_date.' '.$latestAct->formatted_time)
                    : 'Just now',
                'last_active_title' => $latestAct?->formatted_datetime ?? 'Just now',
            ];
        });

    return response()->json([
        'status' => 'success',
        'data' => $staffs,
    ]);
})->name('api.admin.staff.statuses');

// Real-time Activity Logs API
Route::get('/api/admin/activities', function (Request $request) {
    ActivityLogService::syncOverdueBookings();

    $filterWho = $request->query('who', 'all');
    $filterType = $request->query('type', 'all');
    $filterDate = $request->query('date');
    $filterSearch = $request->query('search');

    $logs = ActivityLog::with('staff')
        ->forWho($filterWho)
        ->forType($filterType)
        ->forDate($filterDate)
        ->search($filterSearch)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

    $formatted = $logs->getCollection()->map(function ($log) {
        return [
            'id' => $log->id,
            'activity_type' => $log->activity_type,
            'action' => $log->action,
            'type_badge' => $log->type_badge,
            'actor_name' => $log->actor_name,
            'actor_username' => $log->actor_username,
            'staff_id' => $log->staff_id,
            'is_system' => is_null($log->staff_id),
            'guest_name' => $log->guest_name,
            'room_number' => $log->room_number,
            'booking_type' => $log->booking_type,
            'amount' => $log->amount ? (float) $log->amount : null,
            'amount_formatted' => $log->amount ? '₱'.number_format((float) $log->amount, 2) : null,
            'payment_method' => $log->payment_method,
            'reference_number' => $log->reference_number,
            'description' => $log->description,
            'details' => $log->details,
            'formatted_date' => $log->formatted_date,
            'formatted_time' => $log->formatted_time,
            'formatted_datetime' => $log->formatted_date.' '.$log->formatted_time,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $formatted,
        'current_page' => $logs->currentPage(),
        'last_page' => $logs->lastPage(),
        'per_page' => $logs->perPage(),
        'total' => $logs->total(),
        'from' => $logs->firstItem(),
        'to' => $logs->lastItem(),
    ]);
})->name('api.admin.activities');

// Sales Report CSV & Customizable Excel (.xlsx) Export
Route::get('/admin/sales-report/export', function (Request $request) {
    $format = strtolower((string) $request->input('format', ''));

    if ($format === 'xlsx' || $request->has('header_bg')) {
        $filterParams = [
            'timeframe' => $request->input('timeframe'),
            'year' => $request->input('year'),
            'month' => $request->input('month'),
            'date' => $request->input('date'),
        ];

        $colors = [
            'header_bg' => (string) $request->input('header_bg', '#443627'),
            'header_text' => (string) $request->input('header_text', '#FFFFFF'),
            'body_text' => (string) $request->input('body_text', '#212529'),
            'accent_color' => (string) $request->input('accent_color', '#6A4435'),
            'row_bg' => (string) $request->input('row_bg', '#F8F9FA'),
            'summary_color' => (string) $request->input('summary_color', '#2E7D32'),
        ];

        return app(SalesReportExcelExportService::class)->export($filterParams, $colors);
    }

    $now = now()->setTimezone('Asia/Manila');
    $currentYear = (int) $now->format('Y');
    $currentMonth = (int) $now->format('n');

    $selectedYear = (int) $request->input('year', $currentYear);
    $monthInput = $request->input('month', (string) $currentMonth);
    $selectedDate = $request->input('date');

    $isSpecificDate = ! empty($selectedDate) && strtotime((string) $selectedDate) !== false;
    $isAllMonths = ($monthInput === 'all');
    $selectedMonth = $isAllMonths ? null : (int) $monthInput;

    if ($isSpecificDate) {
        $rangeStart = Carbon::parse($selectedDate, 'Asia/Manila')->startOfDay();
        $rangeEnd = Carbon::parse($selectedDate, 'Asia/Manila')->endOfDay();
        $periodLabel = $rangeStart->format('M_j_Y');
    } elseif (! $isAllMonths && $selectedMonth) {
        $rangeStart = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        $rangeEnd = $rangeStart->copy()->endOfMonth();
        $periodLabel = $rangeStart->format('F_Y');
    } else {
        $rangeStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
        $rangeEnd = $rangeStart->copy()->endOfYear();
        $periodLabel = 'Year_'.$selectedYear;
    }

    // Revenue from payment logs
    $paymentLogs = PaymentLog::where('status', 'successful')
        ->where(function ($q) use ($rangeStart, $rangeEnd) {
            $q->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                    $sub->whereNull('paid_at')->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                });
        })
        ->with('invoice.booking.guest')
        ->get();

    $totalRevenue = (float) $paymentLogs->sum('amount_paid');

    $paidInvoices = Invoice::where('status', 'paid')
        ->where(function ($q) use ($rangeStart, $rangeEnd) {
            $q->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                ->orWhereHas('paymentLogs', function ($plQ) use ($rangeStart, $rangeEnd) {
                    $plQ->where('status', 'successful')
                        ->where(function ($inner) use ($rangeStart, $rangeEnd) {
                            $inner->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                                ->orWhereBetween('created_at', [$rangeStart, $rangeEnd]);
                        });
                });
        })
        ->with(['booking.guest', 'booking.charges'])
        ->get();

    if ($totalRevenue == 0 && $paidInvoices->isNotEmpty()) {
        $totalRevenue = (float) $paidInvoices->sum('grand_total');
    }

    $totalRoomCharges = (float) $paidInvoices->sum('room_charges');
    $totalExtraCharges = (float) $paidInvoices->sum('extra_charges_total');
    $totalRoomNights = 0;

    foreach ($paidInvoices as $inv) {
        $booking = $inv->booking;
        if ($booking) {
            $roomsCount = is_array($booking->booked_rooms) ? count($booking->booked_rooms) : 1;
            $totalRoomNights += ($roomsCount * ($booking->nights ?: 1));
        } else {
            $totalRoomNights += 1;
        }
    }

    $adr = $totalRoomNights > 0 ? round($totalRoomCharges / $totalRoomNights, 2) : 0.00;
    $totalBookings = $paidInvoices->pluck('booking_id')->filter()->unique()->count() ?: $paidInvoices->count();

    $cancelledReservations = Reservation::where('status', 'cancelled')
        ->whereBetween('updated_at', [$rangeStart, $rangeEnd])->get();
    $cancelledBookings = Booking::where('status', 'cancelled')
        ->whereBetween('updated_at', [$rangeStart, $rangeEnd])->get();
    $cancellationsCount = $cancelledReservations->count() + $cancelledBookings->count();
    $lostRevenue = (float) $cancelledReservations->sum('deposit') + (float) $cancelledBookings->sum('deposit');
    if ($lostRevenue == 0 && $cancellationsCount > 0) {
        foreach ($cancelledReservations as $res) {
            $rooms = is_array($res->selected_rooms) ? $res->selected_rooms : (json_decode($res->selected_rooms, true) ?? []);
            $lostRevenue += count($rooms) * 2200;
        }
        foreach ($cancelledBookings as $bk) {
            $rooms = is_array($bk->booked_rooms) ? $bk->booked_rooms : (json_decode($bk->booked_rooms, true) ?? []);
            $lostRevenue += count($rooms) * 2200;
        }
    }

    $pendingInvoices = Invoice::where('status', 'unpaid')->get();
    $pendingGuestsCount = $pendingInvoices->pluck('booking_id')->filter()->unique()->count() ?: $pendingInvoices->count();
    $pendingAmount = (float) $pendingInvoices->sum('grand_total');

    $targetMonth = $selectedMonth ?: $currentMonth;
    $targetRecord = MonthlyTarget::where('year', $selectedYear)->where('month', $targetMonth)->first();
    $monthlyTarget = $targetRecord ? (float) $targetRecord->target_amount : 150000.00;

    // Build invoice rows for the detail section
    $rows = $paidInvoices->map(function ($inv) {
        $booking = $inv->booking;
        $guest = $booking?->guest;
        $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'N/A';
        $rooms = $booking ? (is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? [])) : [];
        $roomStr = ! empty($rooms) ? 'Room '.implode(', ', $rooms) : 'N/A';
        $paidAt = $inv->updated_at ? Carbon::parse($inv->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i') : 'N/A';

        return [
            'Invoice #' => $inv->id,
            'Guest Name' => $guestName,
            'Room(s)' => $roomStr,
            'Nights' => $booking?->nights ?? 'N/A',
            'Stay Type' => ucwords(str_replace('_', ' ', $booking?->stay_type ?? 'N/A')),
            'Room Charges (PHP)' => number_format((float) $inv->room_charges, 2),
            'Extra Charges (PHP)' => number_format((float) $inv->extra_charges_total, 2),
            'Grand Total (PHP)' => number_format((float) $inv->grand_total, 2),
            'Status' => ucfirst($inv->status ?? 'paid'),
            'Paid At' => $paidAt,
        ];
    });

    $filename = 'Sales_Report_'.$periodLabel.'.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0',
    ];

    $callback = function () use ($rows, $totalRevenue, $totalRoomCharges, $totalExtraCharges, $adr, $totalBookings, $monthlyTarget, $lostRevenue, $cancellationsCount, $pendingAmount, $pendingGuestsCount, $periodLabel) {
        $handle = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // ---- Summary section ----
        fputcsv($handle, ['SALES REPORT SUMMARY']);
        fputcsv($handle, ['Period', str_replace('_', ' ', $periodLabel)]);
        fputcsv($handle, ['Generated At', now()->setTimezone('Asia/Manila')->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Metric', 'Value']);
        fputcsv($handle, ['Total Revenue (PHP)', number_format($totalRevenue, 2)]);
        fputcsv($handle, ['Average Daily Rate / ADR (PHP)', number_format($adr, 2)]);
        fputcsv($handle, ['Total Bookings', $totalBookings]);
        fputcsv($handle, ['Monthly Target (PHP)', number_format($monthlyTarget, 2)]);
        fputcsv($handle, ['Room Charges (PHP)', number_format($totalRoomCharges, 2)]);
        fputcsv($handle, ['Extra Charges (PHP)', number_format($totalExtraCharges, 2)]);
        fputcsv($handle, ['Lost Revenue - Cancellations (PHP)', number_format($lostRevenue, 2)]);
        fputcsv($handle, ['Cancellations Count', $cancellationsCount]);
        fputcsv($handle, ['Pending Payments (PHP)', number_format($pendingAmount, 2)]);
        fputcsv($handle, ['Pending Guests Count', $pendingGuestsCount]);
        fputcsv($handle, []);

        // ---- Detailed invoices section ----
        if ($rows->isNotEmpty()) {
            fputcsv($handle, ['PAID INVOICES DETAIL']);
            fputcsv($handle, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }
        } else {
            fputcsv($handle, ['No paid invoices found for this period.']);
        }

        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
})->name('admin.sales-report.export');

// Reservation edit & delete
Route::put('/reservation/{id}', [ReservationController::class, 'update'])->name('reservation.update');
Route::delete('/reservation/{id}', [ReservationController::class, 'destroy'])->name('reservation.destroy');

// Booking edit & delete
Route::put('/bookings/{id}', [BookingController::class, 'update'])->name('booking.update');
Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
