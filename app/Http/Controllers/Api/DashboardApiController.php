<?php

namespace App\Http\Controllers\Api;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\MonthlyTarget;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Services\ActivityLogService;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController
{
    /**
     * Returns guests whose check-out is due within 30 minutes or already overdue,
     * along with current KPI counts and room status mapping for real-time dashboard updates.
     */
    public function notifications()
    {
        $now = now()->setTimezone('Asia/Manila');

        // Automatically detect and log overdue bookings (system-generated)
        ActivityLogService::syncOverdueBookings();

        // Collect guests due within the next 30 minutes or already past due
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

            return [
                'name' => trim(($invoice->booking->guest->fname ?? 'Guest').' '.($invoice->booking->guest->lname ?? '')),
                'room_number' => collect($invoice->booking->booked_rooms)
                    ->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))
                    ->implode(', '),
                'checkout_time' => SystemFormatService::formatTime($checkOut),
                'is_overdue' => $checkOut->lte($now),
                'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
            ];
        })->values();

        // Automatically mark expired blocks as inactive
        RoomBlock::where('is_active', true)
            ->where('end_date', '<', $now)
            ->update(['is_active' => false]);

        // Calculate accurate room statuses and checkouts matching DashboardController
        $rooms = Room::all();
        $roomStatuses = [];
        $roomCheckouts = [];
        $roomTimes = [];
        $roomBlockingPeriods = [];
        $roomReservationArrivals = [];

        foreach ($rooms as $room) {
            $booking = Booking::where(function ($q) use ($room) {
                $q->whereJsonContains('booked_rooms', (string) $room->id)
                    ->orWhereJsonContains('booked_rooms', $room->id);
            })
                ->where('status', 'checked_in')
                ->where('check_in_datetime', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where('check_out_datetime', '>=', $now)
                        ->orWhere('status', 'checked_in');
                })
                ->orderByDesc('check_in_datetime')
                ->first();

            $block = RoomBlock::where('room_id', $room->id)
                ->where('is_active', true)
                ->where('end_date', '>=', $now)
                ->orderBy('start_date')
                ->first();

            if ($block) {
                $status = 'under_maintenance';
                $startFormatted = SystemFormatService::formatDateTime($block->start_date);
                $endFormatted = SystemFormatService::formatDateTime($block->end_date);
                $roomBlockingPeriods[$room->id] = "Start: {$startFormatted} and End: {$endFormatted}";
            } elseif ($booking) {
                $status = 'occupied';
                $checkIn = Carbon::parse($booking->check_in_datetime);
                $checkOut = Carbon::parse($booking->check_out_datetime);
                $roomCheckouts[$room->id] = $checkOut->toIso8601String();
                $roomTimes[$room->id] = [
                    'in' => SystemFormatService::formatTime($checkIn),
                    'out' => SystemFormatService::formatTime($checkOut),
                ];
            } else {
                $reservation = Reservation::where(function ($q) use ($room) {
                    $q->whereJsonContains('selected_rooms', (string) $room->id)
                        ->orWhereJsonContains('selected_rooms', $room->id);
                })
                    ->where('status', 'reserved')
                    ->where('reservation_end_date', '>=', $now)
                    ->orderBy('reservation_start_date')
                    ->first();

                $reservedBooking = Booking::where(function ($q) use ($room) {
                    $q->whereJsonContains('booked_rooms', (string) $room->id)
                        ->orWhereJsonContains('booked_rooms', $room->id);
                })
                    ->where('status', 'reserved')
                    ->where('check_out_datetime', '>=', $now)
                    ->orderBy('check_in_datetime')
                    ->first();

                if ($reservation || $reservedBooking || $room->status === 'reserved') {
                    $status = 'reserved';
                    if ($reservation) {
                        $arrivalStart = Carbon::parse($reservation->reservation_start_date);
                        $roomReservationArrivals[$room->id] = SystemFormatService::formatDateTime($arrivalStart);
                    } elseif ($reservedBooking) {
                        $arrivalStart = Carbon::parse($reservedBooking->check_in_datetime);
                        $roomReservationArrivals[$room->id] = SystemFormatService::formatDateTime($arrivalStart);
                    }
                } else {
                    $status = 'available';
                }
            }

            $roomStatuses[$room->id] = $status;
        }

        // Current KPI counts for the Overview cards
        $occupiedCount = collect($roomStatuses)->filter(fn ($s) => $s === 'occupied')->count();
        $availableCount = collect($roomStatuses)->filter(fn ($s) => $s === 'available')->count();
        $reservedCount = collect($roomStatuses)->filter(fn ($s) => $s === 'reserved')->count();
        $maintenanceCount = collect($roomStatuses)->filter(fn ($s) => $s === 'under_maintenance')->count();
        $totalRooms = $rooms->count();

        return response()->json([
            'due_guests' => $dueGuests,
            'kpi' => [
                'available' => $availableCount,
                'occupied' => $occupiedCount,
                'reserved' => $reservedCount,
                'maintenance' => $maintenanceCount,
                'total' => $totalRooms,
            ],
            'room_statuses' => $roomStatuses,
            'room_checkouts' => $roomCheckouts,
            'room_times' => $roomTimes,
            'room_blocking_periods' => $roomBlockingPeriods,
            'room_reservation_arrivals' => $roomReservationArrivals,
            'amenities' => Amenity::where('offered', true)->orderBy('name', 'asc')->get()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'item_name' => $a->name,
                'price' => (float) $a->price,
                'price_formatted' => number_format((float) $a->price, 2),
                'charged' => $a->charged ?? 'item',
                'offered' => (bool) $a->offered,
                'is_available' => (bool) $a->offered,
            ]),
            'server_time' => $now->toIso8601String(),
        ]);
    }

    /**
     * Real-time amenities endpoint for Receptionist dropdown and Available Amenities list synchronization.
     */
    public function amenities(Request $request)
    {
        $query = Amenity::query()->orderBy('name', 'asc');

        if (! $request->boolean('all')) {
            $query->where('offered', true);
        }

        $amenities = $query->get()->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'item_name' => $a->name,
            'price' => (float) $a->price,
            'price_formatted' => number_format((float) $a->price, 2),
            'charged' => $a->charged ?? 'item',
            'offered' => (bool) $a->offered,
            'is_available' => (bool) $a->offered,
        ]);

        return response()->json([
            'status' => 'success',
            'amenities' => $amenities,
            'count' => $amenities->count(),
            'server_time' => now()->setTimezone('Asia/Manila')->toIso8601String(),
        ]);
    }

    /**
     * Real-time overview statistics for the Administrator dashboard (current day only, Asia/Manila).
     */
    public function adminOverviewStats()
    {
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
            $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : json_decode($booking->booked_rooms, true);
            if (! empty($rooms)) {
                foreach ($rooms as $rId) {
                    $occupiedRoomIds->push((int) $rId);
                }
            }
        }
        $occupancyToday = $occupiedRoomIds->unique()->count();
        $totalRooms = Room::count();

        // 2. Revenue Today: total money actually collected from guests today
        $revenueToday = (float) PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($today) {
                $q->whereDate('paid_at', $today)
                    ->orWhereDate('created_at', $today);
            })
            ->sum('amount_paid');

        // 3. Check-ins Today: all guests who actually checked in today (excluding reservations still marked 'reserved')
        $checkInsList = Booking::with('guest')
            ->where(function ($q) use ($today) {
                $q->whereDate('check_in_datetime', $today)
                    ->orWhereDate('created_at', $today);
            })
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->orderByDesc('check_in_datetime')
            ->get();

        $checkInsToday = $checkInsList->count();

        // 4. Check-outs Today: total guests who have checked out of their occupied rooms today
        $checkOutsList = Booking::with('guest')
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

        $checkOutsToday = $checkOutsList->count();

        $checkInsFormatted = $checkInsList->map(function ($b) {
            $name = trim(($b->guest->fname ?? 'Guest').' '.($b->guest->lname ?? ''));
            $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
            $roomStr = ! empty($rooms) ? 'Room '.implode(', ', $rooms) : 'Room N/A';
            $timeStr = $b->check_in_datetime ? SystemFormatService::formatTime($b->check_in_datetime) : '';

            return [
                'name' => $name,
                'data' => $timeStr ? "{$roomStr} • {$timeStr}" : $roomStr,
            ];
        });

        $checkOutsFormatted = $checkOutsList->map(function ($b) {
            $name = trim(($b->guest->fname ?? 'Guest').' '.($b->guest->lname ?? ''));
            $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
            $roomStr = ! empty($rooms) ? 'Room '.implode(', ', $rooms) : 'Room N/A';
            $time = $b->actual_check_out ?? $b->check_out_datetime;
            $timeStr = $time ? SystemFormatService::formatTime($time) : '';

            return [
                'name' => $name,
                'data' => $timeStr ? "{$roomStr} • {$timeStr}" : $roomStr,
            ];
        });

        return response()->json([
            'occupancy_today' => $occupancyToday,
            'total_rooms' => $totalRooms,
            'revenue_today' => $revenueToday,
            'revenue_today_formatted' => '₱'.number_format($revenueToday, 2),
            'checkins_today' => $checkInsToday,
            'checkouts_today' => $checkOutsToday,
            'checkins_list' => $checkInsFormatted,
            'checkouts_list' => $checkOutsFormatted,
            'revenue_chart' => $this->buildRevenueStatisticsData(),
            'stay_type_chart' => $this->buildStayTypeData('monthly', (int) now()->format('Y'), (int) now()->format('n')),
        ]);
    }

    /**
     * API endpoint for Booking Stay Type donut chart in Administrator Overview.
     * Categorizes actual booking records from Receptionist Booking History into:
     * - Short Stays: 3 Hours, 6 Hours, 12 Hours
     * - Long Stays: 24 Hours
     * Supports specific day selection via calendar filter alongside Weekly, Monthly, and Yearly timeframes.
     */
    public function stayTypeStats(Request $request)
    {
        $now = now()->setTimezone('Asia/Manila');
        $timeframe = strtolower(trim((string) $request->input('timeframe', 'monthly')));
        if (! in_array($timeframe, ['weekly', 'monthly', 'yearly'])) {
            $timeframe = 'monthly';
        }

        $dateInput = $request->input('date');
        $yearInput = $request->input('year');
        $monthInput = $request->input('month');
        $weekInput = $request->input('week', 'all');

        $specificDate = null;
        if (! empty($dateInput) && strtotime((string) $dateInput) !== false) {
            $parsedDate = Carbon::parse($dateInput, 'Asia/Manila');
            $specificDate = $parsedDate->toDateString();
            $year = ($yearInput !== null && $yearInput !== 'all' && $yearInput !== '') ? (int) $yearInput : (int) $parsedDate->format('Y');
            $month = ($monthInput !== null && $monthInput !== 'all' && $monthInput !== '') ? (int) $monthInput : (int) $parsedDate->format('n');
            $day = (int) $parsedDate->format('j');
            $week = ($weekInput && $weekInput !== 'all') ? $weekInput : min(5, (int) ceil($day / 7));
        } else {
            $year = ($yearInput !== null && $yearInput !== 'all' && $yearInput !== '') ? (int) $yearInput : (int) $now->format('Y');
            $month = ($monthInput !== null && $monthInput !== 'all' && $monthInput !== '') ? (int) $monthInput : (($timeframe === 'weekly') ? (int) $now->format('n') : 'all');
            $week = ($weekInput && $weekInput !== '') ? $weekInput : 'all';
        }

        $allYears = ($yearInput === 'all');
        $allMonths = ($monthInput === 'all');

        return response()->json($this->buildStayTypeData($timeframe, $year, $month, $week, $allYears, $allMonths, $specificDate));
    }

    /**
     * Builds Booking Stay Type statistics (counts and percentage shares)
     * from actual Receptionist Booking History records.
     * When $specificDate is provided, filters for that exact day.
     */
    public function buildStayTypeData(
        string $timeframe = 'monthly',
        int $year = 2026,
        int|string $month = 'all',
        int|string $week = 'all',
        bool $allYears = false,
        bool $allMonths = false,
        ?string $specificDate = null
    ): array {
        $now = now()->setTimezone('Asia/Manila');

        $isExactDate = (! empty($specificDate) && strtotime((string) $specificDate) !== false);

        if ($isExactDate) {
            $parsedExact = Carbon::parse($specificDate, 'Asia/Manila');
            $rangeStart = $parsedExact->copy()->startOfDay();
            $rangeEnd = $parsedExact->copy()->endOfDay();
        } elseif ($timeframe === 'yearly') {
            if ($allYears) {
                $rangeStart = Carbon::create(2022, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
                $rangeEnd = $now->copy()->addYears(2)->endOfYear();
            } else {
                $rangeStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
                $rangeEnd = $rangeStart->copy()->endOfYear();
            }
        } elseif ($timeframe === 'weekly') {
            $m = ($month === 'all') ? (int) $now->format('n') : (int) $month;
            $monthStart = Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            if ($week !== 'all' && (int) $week >= 1 && (int) $week <= 5) {
                $w = (int) $week;
                $startDay = (($w - 1) * 7) + 1;
                $endDay = min($monthEnd->day, $w * 7);
                $rangeStart = Carbon::create($year, $m, $startDay, 0, 0, 0, 'Asia/Manila')->startOfDay();
                $rangeEnd = Carbon::create($year, $m, $endDay, 0, 0, 0, 'Asia/Manila')->endOfDay();
            } else {
                $rangeStart = $monthStart;
                $rangeEnd = $monthEnd;
            }
        } else { // monthly
            if ($month === 'all' || $allMonths) {
                $rangeStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
                $rangeEnd = $rangeStart->copy()->endOfYear();
            } else {
                $m = (int) $month;
                $rangeStart = Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
                $rangeEnd = $rangeStart->copy()->endOfMonth();
            }
        }

        // Fetch actual bookings from Receptionist Booking History within the date range
        $bookings = Booking::where(function ($q) use ($rangeStart, $rangeEnd) {
            $q->whereBetween('check_in_datetime', [$rangeStart, $rangeEnd])
                ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                    $sub->whereNull('check_in_datetime')
                        ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                });
        })->get();

        $counts = [
            '3_hours' => 0,
            '6_hours' => 0,
            '12_hours' => 0,
            '24_hours' => 0,
        ];

        foreach ($bookings as $b) {
            $cat = $this->categorizeBookingStayDuration($b);
            $counts[$cat]++;
        }

        $total = array_sum($counts);
        $percentages = [
            '3_hours' => $total > 0 ? round(($counts['3_hours'] / $total) * 100, 1) : 0.0,
            '6_hours' => $total > 0 ? round(($counts['6_hours'] / $total) * 100, 1) : 0.0,
            '12_hours' => $total > 0 ? round(($counts['12_hours'] / $total) * 100, 1) : 0.0,
            '24_hours' => $total > 0 ? round(($counts['24_hours'] / $total) * 100, 1) : 0.0,
        ];

        // Grouped data breakdown depending on timeframe:
        // Monthly: grouped by the 12 calendar months (Jan - Dec)
        // Weekly: grouped into five weeks (Week 1 to Week 5)
        // Yearly: grouped by calendar year (e.g. 2022 - 2026)
        $groupedData = [];

        if ($timeframe === 'monthly') {
            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
                $mEnd = $mStart->copy()->endOfMonth();
                $mBookings = Booking::where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('check_in_datetime', [$mStart, $mEnd])
                        ->orWhere(function ($sub) use ($mStart, $mEnd) {
                            $sub->whereNull('check_in_datetime')
                                ->whereBetween('created_at', [$mStart, $mEnd]);
                        });
                })->get();

                $mCounts = ['3_hours' => 0, '6_hours' => 0, '12_hours' => 0, '24_hours' => 0];
                foreach ($mBookings as $mb) {
                    $mCounts[$this->categorizeBookingStayDuration($mb)]++;
                }
                $mTotal = array_sum($mCounts);

                $groupedData[] = [
                    'label' => Carbon::create()->month($m)->format('F'),
                    'month' => $m,
                    'year' => $year,
                    'counts' => $mCounts,
                    'total' => $mTotal,
                ];
            }
        } elseif ($timeframe === 'weekly') {
            $m = ($month === 'all') ? (int) $now->format('n') : (int) $month;
            $mEnd = Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Manila')->endOfMonth();

            for ($w = 1; $w <= 5; $w++) {
                $startDay = (($w - 1) * 7) + 1;
                $endDay = min($mEnd->day, $w * 7);
                $wStart = Carbon::create($year, $m, $startDay, 0, 0, 0, 'Asia/Manila')->startOfDay();
                $wEnd = Carbon::create($year, $m, $endDay, 0, 0, 0, 'Asia/Manila')->endOfDay();

                $wBookings = Booking::where(function ($q) use ($wStart, $wEnd) {
                    $q->whereBetween('check_in_datetime', [$wStart, $wEnd])
                        ->orWhere(function ($sub) use ($wStart, $wEnd) {
                            $sub->whereNull('check_in_datetime')
                                ->whereBetween('created_at', [$wStart, $wEnd]);
                        });
                })->get();

                $wCounts = ['3_hours' => 0, '6_hours' => 0, '12_hours' => 0, '24_hours' => 0];
                foreach ($wBookings as $wb) {
                    $wCounts[$this->categorizeBookingStayDuration($wb)]++;
                }
                $wTotal = array_sum($wCounts);

                $groupedData[] = [
                    'label' => 'Week '.$w,
                    'week' => $w,
                    'month' => $m,
                    'year' => $year,
                    'counts' => $wCounts,
                    'total' => $wTotal,
                ];
            }
        } elseif ($timeframe === 'yearly') {
            for ($y = 2022; $y <= 2026; $y++) {
                $yStart = Carbon::create($y, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
                $yEnd = $yStart->copy()->endOfYear();

                $yBookings = Booking::where(function ($q) use ($yStart, $yEnd) {
                    $q->whereBetween('check_in_datetime', [$yStart, $yEnd])
                        ->orWhere(function ($sub) use ($yStart, $yEnd) {
                            $sub->whereNull('check_in_datetime')
                                ->whereBetween('created_at', [$yStart, $yEnd]);
                        });
                })->get();

                $yCounts = ['3_hours' => 0, '6_hours' => 0, '12_hours' => 0, '24_hours' => 0];
                foreach ($yBookings as $yb) {
                    $yCounts[$this->categorizeBookingStayDuration($yb)]++;
                }
                $yTotal = array_sum($yCounts);

                $groupedData[] = [
                    'label' => (string) $y,
                    'year' => $y,
                    'counts' => $yCounts,
                    'total' => $yTotal,
                ];
            }
        }

        return [
            'is_exact_date' => $isExactDate,
            'selected_date' => $specificDate,
            'selected_date_formatted' => $isExactDate ? Carbon::parse($specificDate)->format('M d, Y') : null,
            'timeframe' => $timeframe,
            'year' => $year,
            'month' => $month,
            'week' => $week,
            'total' => $total,
            'labels' => ['3 Hours', '6 Hours', '12 Hours', '24 Hours'],
            'counts' => [
                $counts['3_hours'],
                $counts['6_hours'],
                $counts['12_hours'],
                $counts['24_hours'],
            ],
            'percentages' => [
                $percentages['3_hours'],
                $percentages['6_hours'],
                $percentages['12_hours'],
                $percentages['24_hours'],
            ],
            'colors' => ['#30AFFF', '#10B981', '#F59E0B', '#6A4435'],
            'categories' => [
                [
                    'key' => '3_hours',
                    'label' => '3 Hours',
                    'group' => 'Short Stays',
                    'color' => '#30AFFF',
                    'count' => $counts['3_hours'],
                    'percentage' => $percentages['3_hours'],
                ],
                [
                    'key' => '6_hours',
                    'label' => '6 Hours',
                    'group' => 'Short Stays',
                    'color' => '#10B981',
                    'count' => $counts['6_hours'],
                    'percentage' => $percentages['6_hours'],
                ],
                [
                    'key' => '12_hours',
                    'label' => '12 Hours',
                    'group' => 'Short Stays',
                    'color' => '#F59E0B',
                    'count' => $counts['12_hours'],
                    'percentage' => $percentages['12_hours'],
                ],
                [
                    'key' => '24_hours',
                    'label' => '24 Hours',
                    'group' => 'Long Stays',
                    'color' => '#6A4435',
                    'count' => $counts['24_hours'],
                    'percentage' => $percentages['24_hours'],
                ],
            ],
            'grouped_data' => $groupedData,
        ];
    }

    /**
     * Categorizes a booking duration based on actual Receptionist Booking History fields:
     * - Short Stays: 3 Hours, 6 Hours, 12 Hours
     * - Long Stays: 24 Hours
     */
    public function categorizeBookingStayDuration(Booking $b): string
    {
        // 1. Explicit short stay hours
        $hours = (int) ($b->short_stay_hours ?? 0);
        if ($hours === 3) {
            return '3_hours';
        }
        if ($hours === 6) {
            return '6_hours';
        }
        if ($hours === 12) {
            return '12_hours';
        }
        if ($hours === 24) {
            return '24_hours';
        }

        // 2. Based on stay_type and datetime difference
        $stayType = strtolower(trim((string) ($b->stay_type ?? 'overnight')));
        if ($stayType === 'short_stay') {
            if ($b->check_in_datetime && $b->check_out_datetime) {
                $diff = abs(Carbon::parse($b->check_in_datetime)->diffInHours(Carbon::parse($b->check_out_datetime)));
                if ($diff <= 4) {
                    return '3_hours';
                }
                if ($diff <= 8) {
                    return '6_hours';
                }
                if ($diff <= 16) {
                    return '12_hours';
                }
            }

            return '3_hours'; // Fallback for unspecified short stay
        }

        // 3. Overnight / 24 hours / long stays
        return '24_hours';
    }

    /**
     * Builds live Revenue Statistics datasets for Today, Weekly, Monthly, and Yearly
     * connected directly to Receptionist Billing confirmed payments.
     */
    public function buildRevenueStatisticsData(): array
    {
        $now = now()->setTimezone('Asia/Manila');
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');
        $todayDate = $now->toDateString();

        // 1. TODAY: Breakdown by 2-hour frontdesk operating intervals:
        // ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM']
        $todayLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM'];
        $todayData = array_fill(0, count($todayLabels), 0.0);

        $todayLogs = PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($todayDate) {
                $q->whereDate('paid_at', $todayDate)
                    ->orWhereDate('created_at', $todayDate);
            })
            ->get();

        if ($todayLogs->isEmpty()) {
            // Fallback to paid invoices updated today
            $todayInvoices = Invoice::where('status', 'paid')
                ->whereDate('updated_at', $todayDate)
                ->get();

            foreach ($todayInvoices as $inv) {
                $hour = (int) Carbon::parse($inv->updated_at)->setTimezone('Asia/Manila')->format('G');
                $amount = (float) $inv->grand_total;
                $slotIndex = match (true) {
                    $hour < 10 => 0, // 8 AM
                    $hour < 12 => 1, // 10 AM
                    $hour < 14 => 2, // 12 PM
                    $hour < 16 => 3, // 2 PM
                    $hour < 18 => 4, // 4 PM
                    $hour < 20 => 5, // 6 PM
                    default => 6,    // 8 PM
                };
                $todayData[$slotIndex] += $amount;
            }
        } else {
            foreach ($todayLogs as $log) {
                $time = $log->paid_at ?? $log->created_at;
                $hour = (int) Carbon::parse($time)->setTimezone('Asia/Manila')->format('G');
                $amount = (float) $log->amount_paid;
                $slotIndex = match (true) {
                    $hour < 10 => 0, // 8 AM
                    $hour < 12 => 1, // 10 AM
                    $hour < 14 => 2, // 12 PM
                    $hour < 16 => 3, // 2 PM
                    $hour < 18 => 4, // 4 PM
                    $hour < 20 => 5, // 6 PM
                    default => 6,    // 8 PM
                };
                $todayData[$slotIndex] += $amount;
            }
        }
        $todayData = array_map(fn ($v) => round($v, 2), $todayData);

        // 2. WEEKLY: Current month's 5 weeks breakdown
        $weeklyLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
        $weeklyData = [];
        $monthStart = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, 'Asia/Manila');

        for ($w = 1; $w <= 5; $w++) {
            $wStart = $monthStart->copy()->addDays(($w - 1) * 7)->startOfDay();
            $wEnd = ($w === 5) ? $monthStart->copy()->endOfMonth() : $wStart->copy()->addDays(6)->endOfDay();

            if ($wStart->month !== $currentMonth) {
                $weeklyData[] = 0.0;

                continue;
            }

            $wRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($wStart, $wEnd) {
                    $q->whereBetween('paid_at', [$wStart, $wEnd])
                        ->orWhere(function ($sub) use ($wStart, $wEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$wStart, $wEnd]);
                        });
                })
                ->sum('amount_paid');

            if ($wRev == 0) {
                $wRev = (float) Invoice::where('status', 'paid')
                    ->whereBetween('updated_at', [$wStart, $wEnd])
                    ->sum('grand_total');
            }

            $weeklyData[] = round($wRev, 2);
        }

        // 3. MONTHLY: All 12 calendar months of the current year
        $monthlyLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $monthlyData = [];

        for ($m = 1; $m <= 12; $m++) {
            $mStart = Carbon::create($currentYear, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();

            $mRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('paid_at', [$mStart, $mEnd])
                        ->orWhere(function ($sub) use ($mStart, $mEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$mStart, $mEnd]);
                        });
                })
                ->sum('amount_paid');

            if ($mRev == 0) {
                $mRev = (float) Invoice::where('status', 'paid')
                    ->whereBetween('updated_at', [$mStart, $mEnd])
                    ->sum('grand_total');
            }

            $monthlyData[] = round($mRev, 2);
        }

        // 4. YEARLY: Past 6 years up to current year
        $yearlyLabels = [];
        $yearlyData = [];
        for ($y = $currentYear - 5; $y <= $currentYear; $y++) {
            $yearlyLabels[] = (string) $y;
            $yStart = Carbon::create($y, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $yEnd = $yStart->copy()->endOfYear();

            $yRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($yStart, $yEnd) {
                    $q->whereBetween('paid_at', [$yStart, $yEnd])
                        ->orWhere(function ($sub) use ($yStart, $yEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$yStart, $yEnd]);
                        });
                })
                ->sum('amount_paid');

            if ($yRev == 0) {
                $yRev = (float) Invoice::where('status', 'paid')
                    ->whereBetween('updated_at', [$yStart, $yEnd])
                    ->sum('grand_total');
            }

            $yearlyData[] = round($yRev, 2);
        }

        return [
            'today' => [
                'labels' => $todayLabels,
                'data' => $todayData,
            ],
            'weekly' => [
                'labels' => $weeklyLabels,
                'data' => $weeklyData,
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyData,
            ],
            'yearly' => [
                'labels' => $yearlyLabels,
                'data' => $yearlyData,
            ],
        ];
    }

    /**
     * Dedicated endpoint for Revenue Statistics in Administrator Overview.
     */
    public function revenueStats(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'revenue_chart' => $this->buildRevenueStatisticsData(),
            'server_time' => now()->setTimezone('Asia/Manila')->toIso8601String(),
        ]);
    }

    /**
     * Sales report endpoint connected to Receptionist Billing with date, month, and year filters.
     */
    public function salesReport(Request $request)
    {
        $now = now()->setTimezone('Asia/Manila');
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        $selectedYear = (int) $request->input('year', $currentYear);
        $monthInput = $request->input('month', (string) $currentMonth);
        $selectedDate = $request->input('date'); // 'YYYY-MM-DD' or 'M/D/Y' or null
        $timeframe = $request->input('timeframe'); // 'this_month', 'today', 'yearly', 'monthly', 'weekly', 'custom'

        // If no explicit filter parameter is supplied, default to 'today'
        if (! $request->has('timeframe') && ! $request->has('date') && ! $request->has('month') && ! $request->has('year')) {
            $timeframe = 'today';
        }

        // Normalize M/D/Y to Y-m-d if needed
        if ($selectedDate && str_contains($selectedDate, '/')) {
            try {
                $selectedDate = Carbon::createFromFormat('n/j/Y', $selectedDate, 'Asia/Manila')->toDateString();
            } catch (\Throwable $e) {
                try {
                    $selectedDate = Carbon::parse($selectedDate, 'Asia/Manila')->toDateString();
                } catch (\Throwable $e) {
                }
            }
        }

        // Date range resolution
        $isSpecificDate = ! empty($selectedDate) && strtotime((string) $selectedDate) !== false;
        $isAllMonths = ($monthInput === 'all');
        $selectedMonth = $isAllMonths ? null : (int) $monthInput;

        if ($timeframe === 'today') {
            $rangeStart = $now->copy()->startOfDay();
            $rangeEnd = $now->copy()->endOfDay();
            $periodTitle = $rangeStart->format('M j, Y');
            $revenueCardTitle = 'Total Revenue (Today)';
        } elseif ($timeframe === 'custom' || $isSpecificDate) {
            $targetDate = $selectedDate ?: $now->toDateString();
            $rangeStart = Carbon::parse($targetDate, 'Asia/Manila')->startOfDay();
            $rangeEnd = Carbon::parse($targetDate, 'Asia/Manila')->endOfDay();
            $periodTitle = $rangeStart->format('M j, Y');
            $revenueCardTitle = 'Total Revenue ('.$rangeStart->format('M j').')';
        } elseif ($timeframe === 'weekly') {
            $rangeStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $rangeEnd = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $periodTitle = $rangeStart->format('M j').' – '.$rangeEnd->format('M j, Y');
            $revenueCardTitle = 'Total Revenue (This Week)';
        } elseif ($timeframe === 'yearly' || $isAllMonths) {
            $rangeStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $rangeEnd = $rangeStart->copy()->endOfYear();
            $periodTitle = 'Year '.$selectedYear;
            $revenueCardTitle = 'Total Revenue ('.$selectedYear.')';
        } elseif ($timeframe === 'monthly') {
            $rangeStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $rangeEnd = $rangeStart->copy()->endOfYear();
            $periodTitle = 'Year '.$selectedYear.' (Monthly)';
            $revenueCardTitle = 'Total Revenue ('.$selectedYear.')';
        } elseif (! $isAllMonths && $selectedMonth) {
            $rangeStart = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $rangeEnd = $rangeStart->copy()->endOfMonth();
            $periodTitle = $rangeStart->format('F Y');
            $revenueCardTitle = 'Total Revenue ('.$rangeStart->format('M').')';
        } else {
            $rangeStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $rangeEnd = $rangeStart->copy()->endOfYear();
            $periodTitle = 'Year '.$selectedYear;
            $revenueCardTitle = 'Total Revenue ('.$selectedYear.')';
        }

        // 1. Confirmed Invoices & Revenue from Receptionist Billing
        $paymentLogsQuery = PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                        $sub->whereNull('paid_at')->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                    });
            });
        $totalRevenue = (float) $paymentLogsQuery->sum('amount_paid');

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

        // If payment logs are empty for this period, fallback to paid invoices grand total
        if ($totalRevenue == 0 && $paidInvoices->isNotEmpty()) {
            $totalRevenue = (float) $paidInvoices->sum('grand_total');
        }

        // 2. Average Daily Rate (ADR)
        $totalRoomCharges = (float) $paidInvoices->sum('room_charges');
        $totalRoomNights = 0;
        foreach ($paidInvoices as $inv) {
            $booking = $inv->booking;
            if ($booking) {
                $roomsCount = is_array($booking->booked_rooms) ? count($booking->booked_rooms) : 1;
                $nights = $booking->nights ?: 1;
                $totalRoomNights += ($roomsCount * $nights);
            } else {
                $totalRoomNights += 1;
            }
        }
        $adr = $totalRoomNights > 0 ? round($totalRoomCharges / $totalRoomNights, 2) : 0.00;

        // 3. Total Bookings
        $totalBookings = $paidInvoices->pluck('booking_id')->filter()->unique()->count();
        if ($totalBookings === 0) {
            $totalBookings = $paidInvoices->count();
        }

        // 4. Monthly Target & Monthly Target Progress
        // Original:
        // $targetYear = $selectedYear;
        // $targetMonth = $selectedMonth ?: $currentMonth;
        // $targetRecord = MonthlyTarget::where('year', $targetYear)->where('month', $targetMonth)->first();
        // $monthlyTargetAmount = $targetRecord ? (float) $targetRecord->target_amount : 150000.00;
        if ($timeframe === 'custom' || $isSpecificDate) {
            $targetYear = (int) $rangeStart->format('Y');
            $targetMonth = (int) $rangeStart->format('n');
        } else {
            $targetYear = $selectedYear;
            $targetMonth = $selectedMonth ?: $currentMonth;
        }

        $targetMetrics = $this->calculateMonthlyTargetMetrics($targetYear, $targetMonth);
        $monthlyTargetAmount = $targetMetrics['target_amount'];
        $targetMonthRevenue = $targetMetrics['month_revenue'];
        $monthlyTargetProgress = $targetMetrics['progress_percent'];

        // 5. Lost Revenue (Cancellations)
        $cancelledReservations = Reservation::where('status', 'cancelled')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
            ->get();
        $cancelledBookings = Booking::where('status', 'cancelled')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
            ->get();

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

        // 6. Pending Payments from Receptionist Billing Pending Payments table
        $pendingInvoices = Invoice::where('status', 'unpaid')->get();
        $pendingGuestsCount = $pendingInvoices->pluck('booking_id')->filter()->unique()->count();
        if ($pendingGuestsCount === 0) {
            $pendingGuestsCount = $pendingInvoices->count();
        }
        $pendingAmount = (float) $pendingInvoices->sum('grand_total');

        // 7. Chart: Occupancy & Revenue Trend Datasets
        $totalHotelRooms = Room::count() ?: 20;

        // Weekly breakdown for the active month (Weeks 1 to 5)
        $baseMonth = $selectedMonth ?: $currentMonth;
        $monthForWeeks = Carbon::create($selectedYear, $baseMonth, 1, 0, 0, 0, 'Asia/Manila');
        $weeklyLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
        $weeklyOccupancy = [];
        $weeklyRevenue = [];
        $weeklyRoomRate = [];
        $weeklyExtraCharges = [];

        for ($w = 1; $w <= 5; $w++) {
            $wStart = $monthForWeeks->copy()->addDays(($w - 1) * 7)->startOfDay();
            $wEnd = ($w === 5) ? $monthForWeeks->copy()->endOfMonth() : $wStart->copy()->addDays(6)->endOfDay();
            if ($wStart->month !== $baseMonth) {
                $weeklyOccupancy[] = 0;
                $weeklyRevenue[] = 0;
                $weeklyRoomRate[] = 0;
                $weeklyExtraCharges[] = 0;

                continue;
            }
            $daysInSpan = max(1, $wStart->diffInDays($wEnd) + 1);

            $wPaidInvoices = Invoice::where('status', 'paid')
                ->whereBetween('updated_at', [$wStart, $wEnd])
                ->get();
            $wRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($wStart, $wEnd) {
                    $q->whereBetween('paid_at', [$wStart, $wEnd])
                        ->orWhere(function ($sub) use ($wStart, $wEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$wStart, $wEnd]);
                        });
                })
                ->sum('amount_paid');
            if ($wRev == 0) {
                $wRev = (float) $wPaidInvoices->sum('grand_total');
            }

            // Occupancy calculation for this week
            $wOccupiedBookings = Booking::where('status', '!=', 'cancelled')
                ->where(function ($q) use ($wStart, $wEnd) {
                    $q->whereBetween('check_in_datetime', [$wStart, $wEnd])
                        ->orWhereBetween('actual_check_out', [$wStart, $wEnd])
                        ->orWhere(function ($sub) use ($wStart, $wEnd) {
                            $sub->where('check_in_datetime', '<=', $wStart)
                                ->where('check_out_datetime', '>=', $wEnd);
                        });
                })
                ->get();
            $wRoomCount = 0;
            foreach ($wOccupiedBookings as $b) {
                $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
                $wRoomCount += count($rooms);
            }
            $possibleCapacity = $totalHotelRooms * $daysInSpan;
            $occPercent = $possibleCapacity > 0 ? min(100, round(($wRoomCount / $possibleCapacity) * 100)) : 0;

            $weeklyOccupancy[] = $occPercent;
            $weeklyRevenue[] = round($wRev, 2);
            $weeklyRoomRate[] = round((float) $wPaidInvoices->sum('room_charges'), 2);
            $weeklyExtraCharges[] = round((float) $wPaidInvoices->sum('extra_charges_total'), 2);
        }

        // Monthly breakdown (Jan to Dec of selected year)
        $monthlyLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $monthlyOccupancy = [];
        $monthlyRevenue = [];
        $monthlyRoomRate = [];
        $monthlyExtraCharges = [];

        for ($m = 1; $m <= 12; $m++) {
            $mStart = Carbon::create($selectedYear, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();

            $mPaidInvoices = Invoice::where('status', 'paid')
                ->whereBetween('updated_at', [$mStart, $mEnd])
                ->get();

            $mRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('paid_at', [$mStart, $mEnd])
                        ->orWhere(function ($sub) use ($mStart, $mEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$mStart, $mEnd]);
                        });
                })
                ->sum('amount_paid');
            if ($mRev == 0) {
                $mRev = (float) $mPaidInvoices->sum('grand_total');
            }

            $mOccupiedBookings = Booking::where('status', '!=', 'cancelled')
                ->where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('check_in_datetime', [$mStart, $mEnd])
                        ->orWhereBetween('actual_check_out', [$mStart, $mEnd])
                        ->orWhere(function ($sub) use ($mStart, $mEnd) {
                            $sub->where('check_in_datetime', '<=', $mStart)
                                ->where('check_out_datetime', '>=', $mEnd);
                        });
                })
                ->get();
            $mRoomCount = 0;
            foreach ($mOccupiedBookings as $b) {
                $rooms = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
                $mRoomCount += count($rooms);
            }
            $mCapacity = $totalHotelRooms * $mStart->daysInMonth;
            $mOccPercent = $mCapacity > 0 ? min(100, round(($mRoomCount / $mCapacity) * 100)) : 0;

            $monthlyOccupancy[] = $mOccPercent;
            $monthlyRevenue[] = round($mRev, 2);
            $monthlyRoomRate[] = round((float) $mPaidInvoices->sum('room_charges'), 2);
            $monthlyExtraCharges[] = round((float) $mPaidInvoices->sum('extra_charges_total'), 2);
        }

        // Yearly breakdown (last 5 years)
        $yearlyLabels = [];
        $yearlyOccupancy = [];
        $yearlyRevenue = [];
        for ($y = $selectedYear - 4; $y <= $selectedYear; $y++) {
            $yearlyLabels[] = (string) $y;
            $yStart = Carbon::create($y, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $yEnd = $yStart->copy()->endOfYear();

            $yRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($yStart, $yEnd) {
                    $q->whereBetween('paid_at', [$yStart, $yEnd])
                        ->orWhere(function ($sub) use ($yStart, $yEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$yStart, $yEnd]);
                        });
                })
                ->sum('amount_paid');
            if ($yRev == 0) {
                $yRev = (float) Invoice::where('status', 'paid')
                    ->whereBetween('updated_at', [$yStart, $yEnd])
                    ->sum('grand_total');
            }

            $yCapacity = $totalHotelRooms * 365;
            $yOcc = $yCapacity > 0 ? min(100, round((Booking::where('status', '!=', 'cancelled')->whereBetween('created_at', [$yStart, $yEnd])->count() * 2 / $yCapacity) * 100)) : 0;

            $yearlyRevenue[] = round($yRev, 2);
            $yearlyOccupancy[] = $yOcc;
        }

        // Daily breakdown (hourly intervals for single date: Today or Custom)
        $dailyLabels = ['12 AM - 4 AM', '4 AM - 8 AM', '8 AM - 12 PM', '12 PM - 4 PM', '4 PM - 8 PM', '8 PM - 12 AM'];
        $dailyOccupancy = [];
        $dailyRevenue = [];
        $dayHours = [[0, 4], [4, 8], [8, 12], [12, 16], [16, 20], [20, 24]];
        foreach ($dayHours as [$sh, $eh]) {
            $blockStart = $rangeStart->copy()->setTime($sh, 0, 0);
            $blockEnd = ($eh === 24) ? $rangeStart->copy()->endOfDay() : $rangeStart->copy()->setTime($eh, 0, 0);
            $bRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($blockStart, $blockEnd) {
                    $q->whereBetween('paid_at', [$blockStart, $blockEnd])
                        ->orWhere(function ($sub) use ($blockStart, $blockEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$blockStart, $blockEnd]);
                        });
                })->sum('amount_paid');
            $bOccCount = Booking::where('status', '!=', 'cancelled')
                ->where('check_in_datetime', '<=', $blockEnd)
                ->where(function ($q) use ($blockStart) {
                    $q->where('check_out_datetime', '>=', $blockStart)
                        ->orWhere('status', 'checked_in');
                })->count();
            $dailyOccupancy[] = $totalHotelRooms > 0 ? min(100, round(($bOccCount / $totalHotelRooms) * 100)) : 0;
            $dailyRevenue[] = round($bRev, 2);
        }

        // Days of current week breakdown (for Weekly filter)
        $weekDayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $weekDayOccupancy = [];
        $weekDayRevenue = [];
        for ($d = 0; $d < 7; $d++) {
            $dStart = $rangeStart->copy()->addDays($d)->startOfDay();
            $dEnd = $dStart->copy()->endOfDay();
            $dRev = (float) PaymentLog::where('status', 'successful')
                ->where(function ($q) use ($dStart, $dEnd) {
                    $q->whereBetween('paid_at', [$dStart, $dEnd])
                        ->orWhere(function ($sub) use ($dStart, $dEnd) {
                            $sub->whereNull('paid_at')->whereBetween('created_at', [$dStart, $dEnd]);
                        });
                })->sum('amount_paid');
            $dOcc = Booking::where('status', '!=', 'cancelled')
                ->where('check_in_datetime', '<=', $dEnd)
                ->where(function ($q) use ($dStart) {
                    $q->where('check_out_datetime', '>=', $dStart)
                        ->orWhere('status', 'checked_in');
                })->count();
            $weekDayOccupancy[] = $totalHotelRooms > 0 ? min(100, round(($dOcc / $totalHotelRooms) * 100)) : 0;
            $weekDayRevenue[] = round($dRev, 2);
        }

        // Active trend dataset selection connected to the main Sales Reports filter
        if ($timeframe === 'today' || $timeframe === 'custom' || $isSpecificDate) {
            $activeTrendLabels = $dailyLabels;
            $activeTrendOccupancy = $dailyOccupancy;
            $activeTrendRevenue = $dailyRevenue;
            $activeTrendScope = ($timeframe === 'today') ? 'Today (Hourly Intervals)' : 'Selected Date ('.$periodTitle.')';
        } elseif ($timeframe === 'weekly') {
            $activeTrendLabels = $weekDayLabels;
            $activeTrendOccupancy = $weekDayOccupancy;
            $activeTrendRevenue = $weekDayRevenue;
            $activeTrendScope = 'Weekly (Mon – Sun)';
        } elseif ($timeframe === 'yearly') {
            $activeTrendLabels = $yearlyLabels;
            $activeTrendOccupancy = $yearlyOccupancy;
            $activeTrendRevenue = $yearlyRevenue;
            $activeTrendScope = 'Yearly (5-Year Trend)';
        } elseif ($timeframe === 'monthly') {
            $activeTrendLabels = $monthlyLabels;
            $activeTrendOccupancy = $monthlyOccupancy;
            $activeTrendRevenue = $monthlyRevenue;
            $activeTrendScope = 'Monthly (Jan – Dec)';
        } else {
            $activeTrendLabels = $weeklyLabels;
            $activeTrendOccupancy = $weeklyOccupancy;
            $activeTrendRevenue = $weeklyRevenue;
            $activeTrendScope = 'This Month (Weeks 1 – 5)';
        }

        // 8. Amenities Comparison (Ranked Horizontal Bar Chart)
        $weeklyAmenitiesData = $this->calculateAmenitiesComparisonForRange(
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek(),
            $now->copy()->startOfWeek()->format('M d').' – '.$now->copy()->endOfWeek()->format('M d, Y')
        );

        $monthlyAmenitiesData = $this->calculateAmenitiesComparisonForRange(
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth(),
            $now->copy()->format('F Y')
        );

        $yearlyAmenitiesData = $this->calculateAmenitiesComparisonForRange(
            $now->copy()->startOfYear(),
            $now->copy()->endOfYear(),
            'Year '.$now->copy()->format('Y')
        );

        // Period-specific calculation matching the main report filter for backward compatibility
        $currentFilterAmenities = $this->calculateAmenitiesComparisonForInvoices($paidInvoices, $periodTitle);

        return response()->json([
            'period_title' => $periodTitle,
            'revenue_card_title' => $revenueCardTitle,
            'selected_year' => $selectedYear,
            'selected_month' => $monthInput,
            'selected_date' => $selectedDate,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => '₱'.number_format($totalRevenue, 2),
            'adr' => $adr,
            'adr_formatted' => '₱'.number_format($adr, 2),
            'total_bookings' => $totalBookings,
            'monthly_target' => $monthlyTargetAmount,
            'monthly_target_formatted' => '₱'.number_format($monthlyTargetAmount, 2),
            'monthly_target_progress' => $monthlyTargetProgress,
            'monthly_target_year' => $targetYear,
            'monthly_target_month' => $targetMonth,
            'monthly_target_revenue' => $targetMonthRevenue,
            'monthly_target_revenue_formatted' => '₱'.number_format($targetMonthRevenue, 2),
            'has_custom_target' => $targetMetrics['has_custom_target'],
            'lost_revenue' => $lostRevenue,
            'lost_revenue_formatted' => '-₱'.number_format($lostRevenue, 2),
            'cancellations_count' => $cancellationsCount,
            'pending_amount' => $pendingAmount,
            'pending_amount_formatted' => '₱'.number_format($pendingAmount, 2),
            'pending_guests_count' => $pendingGuestsCount,
            'occupancy_trend' => [
                'active_timeframe' => $timeframe ?: ($isSpecificDate ? 'custom' : 'this_month'),
                'scope' => $activeTrendScope,
                'labels' => $activeTrendLabels,
                'data' => $activeTrendOccupancy,
                'revenue' => $activeTrendRevenue,
                'daily' => [
                    'labels' => $dailyLabels,
                    'data' => $dailyOccupancy,
                    'revenue' => $dailyRevenue,
                ],
                'weekly' => [
                    'labels' => $weeklyLabels,
                    'data' => $weeklyOccupancy,
                    'revenue' => $weeklyRevenue,
                ],
                'monthly' => [
                    'labels' => $monthlyLabels,
                    'data' => $monthlyOccupancy,
                    'revenue' => $monthlyRevenue,
                ],
                'yearly' => [
                    'labels' => $yearlyLabels,
                    'data' => $yearlyOccupancy,
                    'revenue' => $yearlyRevenue,
                ],
            ],
            'revenue_breakdown' => [
                'weekly' => [
                    'labels' => $weeklyLabels,
                    'roomRate' => $weeklyRoomRate,
                    'extraCharges' => $weeklyExtraCharges,
                ],
                'monthly' => [
                    'labels' => $monthlyLabels,
                    'roomRate' => $monthlyRoomRate,
                    'extraCharges' => $monthlyExtraCharges,
                ],
            ],
            'amenities_comparison' => [
                'subtitle' => $weeklyAmenitiesData['subtitle'],
                'items' => $weeklyAmenitiesData['items'],
                'weekly' => $weeklyAmenitiesData,
                'monthly' => $monthlyAmenitiesData,
                'yearly' => $yearlyAmenitiesData,
                'current_filter' => $currentFilterAmenities,
            ],
        ]);
    }

    /**
     * Independent endpoint for Amenities Comparison timeframe filter (weekly, monthly, yearly).
     */
    public function amenitiesComparison(Request $request)
    {
        $now = now()->setTimezone('Asia/Manila');
        $period = strtolower((string) $request->input('period', 'weekly'));
        $yearInput = $request->input('year');
        $year = $yearInput ? (int) $yearInput : (int) $now->format('Y');

        if ($period === 'yearly') {
            $start = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $end = $start->copy()->endOfYear();
            $subtitle = 'Year '.$year;
        } elseif ($period === 'monthly') {
            $monthInput = $request->input('month');
            $month = $monthInput ? (int) $monthInput : (int) $now->format('n');
            $start = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $subtitle = $start->format('F Y');
        } else {
            // weekly
            $period = 'weekly';
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
            $subtitle = $start->format('M d').' – '.$end->format('M d, Y');
        }

        $data = $this->calculateAmenitiesComparisonForRange($start, $end, $subtitle);

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'subtitle' => $data['subtitle'],
            'items' => $data['items'],
        ]);
    }

    /**
     * Helper to compute amenities comparison given a date range.
     */
    private function calculateAmenitiesComparisonForRange(Carbon $start, Carbon $end, string $subtitle): array
    {
        $invoices = Invoice::where('status', 'paid')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('updated_at', [$start, $end])
                    ->orWhereHas('paymentLogs', function ($plQ) use ($start, $end) {
                        $plQ->where('status', 'successful')
                            ->where(function ($inner) use ($start, $end) {
                                $inner->whereBetween('paid_at', [$start, $end])
                                    ->orWhereBetween('created_at', [$start, $end]);
                            });
                    });
            })
            ->get();

        return $this->calculateAmenitiesComparisonForInvoices($invoices, $subtitle);
    }

    /**
     * Helper to compute amenities comparison given a collection of invoices.
     */
    private function calculateAmenitiesComparisonForInvoices($paidInvoices, string $subtitle): array
    {
        $paidInvoiceIds = $paidInvoices->pluck('id');
        $paidBookingIds = $paidInvoices->pluck('booking_id')->filter();

        $amenitiesList = AvailableAmenity::whereIn('invoice_id', $paidInvoiceIds)
            ->orWhere(function ($q) use ($paidBookingIds) {
                $q->whereIn('booking_id', $paidBookingIds);
            })
            ->get();

        $amenitiesGrouped = $amenitiesList->groupBy('item_name')->map(function ($items, $name) {
            $count = (int) $items->sum('quantity') ?: $items->count();
            $revenue = (float) $items->sum(function ($i) {
                return $i->total_amount ?: (($i->price ?: $i->unit_price) * ($i->quantity ?: 1));
            });

            return [
                'name' => $name,
                'count' => $count,
                'revenue' => $revenue,
            ];
        })->values();

        // Fallback to Charge lines if AvailableAmenity rows are empty
        if ($amenitiesGrouped->isEmpty() && $paidBookingIds->isNotEmpty()) {
            $amenitiesGrouped = Charge::whereIn('booking_id', $paidBookingIds)
                ->where('type', '!=', 'room')
                ->get()
                ->groupBy('description')
                ->map(function ($items, $name) {
                    return [
                        'name' => $name,
                        'count' => (int) $items->sum('quantity') ?: $items->count(),
                        'revenue' => (float) $items->sum('amount'),
                    ];
                })
                ->values();
        }

        // If still empty, return catalog amenities with 0
        if ($amenitiesGrouped->isEmpty()) {
            $amenitiesGrouped = Amenity::where('offered', true)->get()->map(function ($a) {
                return [
                    'name' => $a->name,
                    'count' => 0,
                    'revenue' => 0.00,
                ];
            });
        }

        $amenitiesRanked = $amenitiesGrouped->sortByDesc('count')->values();

        return [
            'subtitle' => $subtitle,
            'items' => $amenitiesRanked,
        ];
    }

    /**
     * Calculate monthly target goal, monthly revenue, and progress percentage for a given year and month.
     *
     * @return array{year: int, month: int, target_amount: float, target_formatted: string, month_revenue: float, month_revenue_formatted: string, progress_percent: float|int, has_custom_target: bool, target: ?MonthlyTarget}
     */
    private function calculateMonthlyTargetMetrics(int $year, int $month): array
    {
        $targetRecord = MonthlyTarget::where('year', $year)->where('month', $month)->first();
        $targetAmount = $targetRecord ? (float) $targetRecord->target_amount : 150000.00;

        $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $monthRevenue = (float) PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                        $sub->whereNull('paid_at')->whereBetween('created_at', [$monthStart, $monthEnd]);
                    });
            })
            ->sum('amount_paid');

        if ($monthRevenue == 0) {
            $monthRevenue = (float) Invoice::where('status', 'paid')
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->sum('grand_total');
        }

        $progressPercent = $targetAmount > 0 ? min(100, round(($monthRevenue / $targetAmount) * 100)) : 0;

        return [
            'year' => $year,
            'month' => $month,
            'target_amount' => $targetAmount,
            'target_formatted' => '₱'.number_format($targetAmount, 2),
            'month_revenue' => $monthRevenue,
            'month_revenue_formatted' => '₱'.number_format($monthRevenue, 2),
            'progress_percent' => $progressPercent,
            'has_custom_target' => $targetRecord !== null,
            'target' => $targetRecord,
        ];
    }

    /**
     * Retrieve the saved monthly target and progress for a specific year and month.
     */
    public function getMonthlyTarget(Request $request)
    {
        $now = now()->setTimezone('Asia/Manila');
        $year = (int) $request->input('year', $now->format('Y'));
        $month = (int) $request->input('month', $now->format('n'));

        if ($month < 1 || $month > 12) {
            $month = (int) $now->format('n');
        }
        if ($year < 2020 || $year > 2099) {
            $year = (int) $now->format('Y');
        }

        $metrics = $this->calculateMonthlyTargetMetrics($year, $month);

        return response()->json([
            'status' => 'success',
            'year' => $metrics['year'],
            'month' => $metrics['month'],
            'target_amount' => $metrics['target_amount'],
            'target_formatted' => $metrics['target_formatted'],
            'has_custom_target' => $metrics['has_custom_target'],
            'month_revenue' => $metrics['month_revenue'],
            'month_revenue_formatted' => $metrics['month_revenue_formatted'],
            'progress_percent' => $metrics['progress_percent'],
        ]);
    }

    /**
     * Persist or update the monthly target set from the Administrator dashboard.
     */
    public function saveMonthlyTarget(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            // Original: 'target_amount' => 'required|numeric|min:1',
            'target_amount' => 'required|numeric|min:1|max:999999999.99',
        ]);

        $target = MonthlyTarget::updateOrCreate(
            [
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
            ],
            [
                'target_amount' => round((float) $validated['target_amount'], 2),
            ]
        );

        // Original:
        // $monthStart = Carbon::create((int) $target->year, (int) $target->month, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        // $monthEnd = $monthStart->copy()->endOfMonth();
        // ...
        // $targetAmount = (float) $target->target_amount;
        // $progressPercent = $targetAmount > 0 ? min(100, round(($monthRevenue / $targetAmount) * 100)) : 0;
        $metrics = $this->calculateMonthlyTargetMetrics((int) $target->year, (int) $target->month);

        return response()->json([
            'status' => 'success',
            'message' => 'Monthly target updated successfully.',
            'target' => $target,
            'target_formatted' => $metrics['target_formatted'],
            'month_revenue' => $metrics['month_revenue'],
            'month_revenue_formatted' => $metrics['month_revenue_formatted'],
            'progress_percent' => $metrics['progress_percent'],
        ]);
    }

    /**
     * Admin Overview notifications: upcoming & due guest bookings and cancellation revenue losses.
     */
    public function adminNotifications()
    {
        $now = now()->setTimezone('Asia/Manila');
        $roomsMap = Room::all()->keyBy('id');

        // Helper to format room details
        $formatRoomDetails = function ($roomIds) use ($roomsMap) {
            $ids = is_array($roomIds) ? $roomIds : (json_decode($roomIds, true) ?? []);
            if (empty($ids)) {
                return [
                    'numbers' => 'N/A',
                    'details' => 'No room assigned',
                ];
            }

            $numbers = [];
            $details = [];

            foreach ($ids as $id) {
                $room = $roomsMap->get($id) ?? $roomsMap->firstWhere('room_number', (string) $id);
                $numStr = $room ? str_pad($room->room_number, 2, '0', STR_PAD_LEFT) : str_pad($id, 2, '0', STR_PAD_LEFT);
                $numbers[] = $numStr;

                if ($room) {
                    $details[] = "RM {$numStr} (₱".number_format((float) $room->short_stay_rate, 2).' short / ₱'.number_format((float) $room->overnight_rate, 2).' ovn)';
                } else {
                    $details[] = "RM {$numStr}";
                }
            }

            return [
                'numbers' => implode(', ', $numbers),
                'details' => implode(', ', $details),
            ];
        };

        // -------------------------------------------------------------
        // 1. Upcoming & Due guest bookings (checked-in due/overdue + upcoming)
        // -------------------------------------------------------------
        $dueGuestsList = collect();
        $processedGuestBookingKeys = [];

        // A. Checked-in bookings due for checkout within 30 minutes or already overdue
        $dueBookings = Booking::with(['guest', 'invoice', 'charges'])
            ->where('status', 'checked_in')
            ->where('check_out_datetime', '<=', $now->copy()->addMinutes(30))
            ->where(function ($q) {
                // Must not be an already settled/paid invoice unless still checked in
                $q->whereDoesntHave('invoice')
                    ->orWhereHas('invoice', fn ($i) => $i->where('status', '!=', 'paid'))
                    ->orWhere('status', 'checked_in');
            })
            ->get();

        foreach ($dueBookings as $booking) {
            $key = 'booking_'.$booking->id;
            if (isset($processedGuestBookingKeys[$key])) {
                continue;
            }
            $processedGuestBookingKeys[$key] = true;

            $checkOut = Carbon::parse($booking->check_out_datetime);
            $checkIn = $booking->check_in_datetime ? Carbon::parse($booking->check_in_datetime) : null;
            $roomInfo = $formatRoomDetails($booking->booked_rooms);
            $isOverdue = $checkOut->lte($now);
            $minutes = max(0, (int) floor($now->diffInMinutes($checkOut, false)));

            $totalAmount = (float) (
                $booking->invoice?->grand_total
                ?? ($booking->charges ? $booking->charges->sum('amount') : 0)
                ?? $booking->total_amount
                ?? 0
            );

            $dueGuestsList->push([
                'id' => $booking->id,
                'source_type' => 'Booking',
                'name' => trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')),
                'room_number' => $roomInfo['numbers'],
                'booked_rooms_details' => $roomInfo['details'],
                'stay_type' => ucwords(str_replace('_', ' ', $booking->stay_type ?? 'overnight')),
                'check_in_time' => $checkIn ? SystemFormatService::formatDateTime($checkIn) : 'N/A',
                'checkout_time' => SystemFormatService::formatTime($checkOut),
                'is_overdue' => $isOverdue,
                'is_upcoming' => false,
                'status' => $isOverdue ? 'overdue' : 'due_soon',
                'status_label' => $isOverdue ? 'Overdue' : 'Due Soon',
                'minutes' => $minutes,
                'time_text' => $isOverdue
                    ? 'was due at '.SystemFormatService::formatTime($checkOut).' (overdue)'
                    : SystemFormatService::formatTime($checkOut).' is due checkout ('.$minutes.'m left)',
                'total_amount' => $totalAmount,
                'total_amount_formatted' => '₱'.number_format($totalAmount, 2),
                'sort_order' => $isOverdue ? 1 : 2,
                'raw_time' => $checkOut->timestamp,
            ]);
        }

        // B. Upcoming bookings (scheduled arrival in the future or status reserved)
        $upcomingBookings = Booking::with(['guest', 'invoice', 'charges'])
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where(function ($q) use ($now) {
                $q->where('status', 'reserved')
                    ->orWhere('check_in_datetime', '>', $now);
            })
            ->get();

        foreach ($upcomingBookings as $booking) {
            $key = 'booking_'.$booking->id;
            if (isset($processedGuestBookingKeys[$key])) {
                continue;
            }
            $processedGuestBookingKeys[$key] = true;

            $checkIn = Carbon::parse($booking->check_in_datetime);
            $checkOut = $booking->check_out_datetime ? Carbon::parse($booking->check_out_datetime) : null;
            $roomInfo = $formatRoomDetails($booking->booked_rooms);
            $minutes = max(0, (int) floor($now->diffInMinutes($checkIn, false)));

            $totalAmount = (float) (
                $booking->invoice?->grand_total
                ?? ($booking->charges ? $booking->charges->sum('amount') : 0)
                ?? $booking->total_amount
                ?? 0
            );

            $dueGuestsList->push([
                'id' => $booking->id,
                'source_type' => 'Booking',
                'name' => trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')),
                'room_number' => $roomInfo['numbers'],
                'booked_rooms_details' => $roomInfo['details'],
                'stay_type' => ucwords(str_replace('_', ' ', $booking->stay_type ?? 'overnight')),
                'check_in_time' => SystemFormatService::formatDateTime($checkIn),
                'checkout_time' => $checkOut ? SystemFormatService::formatTime($checkOut) : 'N/A',
                'is_overdue' => false,
                'is_upcoming' => true,
                'status' => 'upcoming',
                'status_label' => ($booking->status === 'reserved') ? 'Reserved' : 'Upcoming',
                'minutes' => $minutes,
                'time_text' => 'Scheduled check-in at '.SystemFormatService::formatDateTime($checkIn),
                'total_amount' => $totalAmount,
                'total_amount_formatted' => '₱'.number_format($totalAmount, 2),
                'sort_order' => 3,
                'raw_time' => $checkIn->timestamp,
            ]);
        }

        // C. Upcoming reservations (reserved status, not yet checked in)
        $upcomingReservations = Reservation::where('status', 'reserved')
            ->where('reservation_end_date', '>=', $now)
            ->get();

        foreach ($upcomingReservations as $res) {
            $key = 'reservation_'.$res->id;
            // Prevent duplicate if already processed under a matching booking
            if (isset($processedGuestBookingKeys[$key])) {
                continue;
            }
            if ($res->guest_id && isset($processedGuestBookingKeys['guest_'.$res->guest_id])) {
                continue;
            }
            $processedGuestBookingKeys[$key] = true;

            $start = $res->reservation_start_date ? Carbon::parse($res->reservation_start_date) : null;
            $end = $res->reservation_end_date ? Carbon::parse($res->reservation_end_date) : null;
            $roomInfo = $formatRoomDetails($res->selected_rooms);
            $minutes = $start ? max(0, (int) floor($now->diffInMinutes($start, false))) : 0;
            $deposit = (float) ($res->deposit ?? 0);

            $dueGuestsList->push([
                'id' => $res->id,
                'source_type' => 'Reservation',
                'name' => $res->guest_name ?: 'Guest',
                'room_number' => $roomInfo['numbers'],
                'booked_rooms_details' => $roomInfo['details'],
                'stay_type' => 'Reserved Stay',
                'check_in_time' => $start ? SystemFormatService::formatDateTime($start) : 'N/A',
                'checkout_time' => $end ? SystemFormatService::formatTime($end) : 'N/A',
                'is_overdue' => false,
                'is_upcoming' => true,
                'status' => 'upcoming',
                'status_label' => 'Reserved',
                'minutes' => $minutes,
                'time_text' => $start ? 'Scheduled check-in at '.SystemFormatService::formatDateTime($start) : 'Reserved Stay',
                'total_amount' => $deposit,
                'total_amount_formatted' => '₱'.number_format($deposit, 2),
                'sort_order' => 3,
                'raw_time' => $start ? $start->timestamp : 0,
            ]);
        }

        // Sort: Overdue (sort_order 1) -> Due Soon (sort_order 2) -> Upcoming (sort_order 3)
        $dueGuests = $dueGuestsList->sort(function ($a, $b) {
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] <=> $b['sort_order'];
            }

            return $a['raw_time'] <=> $b['raw_time'];
        })->values();

        // -------------------------------------------------------------
        // 2. Cancellation revenue loss (Actual room charges & applicable fees)
        // -------------------------------------------------------------
        $cancelledBookings = Booking::with(['guest', 'invoice', 'charges'])
            ->where('status', 'cancelled')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($b) use ($formatRoomDetails, $roomsMap) {
                // Calculate actual room charges and applicable fees
                $roomCharges = 0.0;
                $extraCharges = 0.0;
                $lostAmount = 0.0;

                if ($b->invoice) {
                    $roomCharges = (float) $b->invoice->room_charges;
                    $extraCharges = (float) $b->invoice->extra_charges_total;
                    $grandTotal = (float) $b->invoice->grand_total;
                    $lostAmount = $grandTotal > 0 ? $grandTotal : ($roomCharges + $extraCharges);
                }

                if ($lostAmount <= 0 && $b->charges && $b->charges->isNotEmpty()) {
                    $roomCharges = (float) $b->charges->where('type', 'room')->sum('amount');
                    $extraCharges = (float) $b->charges->where('type', '!=', 'room')->sum('amount');
                    $lostAmount = $roomCharges + $extraCharges;
                }

                if ($lostAmount <= 0 && (float) ($b->deposit ?? 0) > 0) {
                    $lostAmount = (float) $b->deposit;
                    $roomCharges = $lostAmount;
                    $extraCharges = 0.0;
                }

                // Fallback to room standard rates if charges/invoice not recorded
                if ($lostAmount <= 0) {
                    $roomIds = is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? []);
                    $isShort = ($b->stay_type === 'short_stay');
                    $calculatedRoomTotal = 0.0;
                    foreach ($roomIds as $rId) {
                        $rm = $roomsMap->get($rId) ?? $roomsMap->firstWhere('room_number', (string) $rId);
                        if ($rm) {
                            $calculatedRoomTotal += $isShort ? (float) $rm->short_stay_rate : (float) $rm->overnight_rate;
                        }
                    }
                    if ($calculatedRoomTotal > 0) {
                        $roomCharges = $calculatedRoomTotal;
                        $lostAmount = $calculatedRoomTotal;
                    }
                }

                $roomInfo = $formatRoomDetails($b->booked_rooms);
                $guestName = trim(($b->guest->fname ?? 'Guest').' '.($b->guest->lname ?? ''));
                $start = $b->check_in_datetime ? Carbon::parse($b->check_in_datetime)->format('M d, Y') : null;
                $end = $b->check_out_datetime ? Carbon::parse($b->check_out_datetime)->format('M d, Y') : null;
                $dates = ($start && $end) ? "{$start} &ndash; {$end}" : ($start ?: 'N/A');

                return [
                    'id' => $b->id,
                    'type' => 'Booking',
                    'guest_name' => $guestName ?: 'Guest',
                    'room_number' => $roomInfo['numbers'],
                    'booked_rooms_details' => $roomInfo['details'],
                    'dates' => $dates,
                    'stay_type' => ucwords(str_replace('_', ' ', $b->stay_type ?? 'overnight')),
                    'deposit' => $lostAmount,
                    'deposit_formatted' => '₱'.number_format($lostAmount, 2),
                    'lost_revenue' => $lostAmount,
                    'lost_revenue_formatted' => '₱'.number_format($lostAmount, 2),
                    'room_charges' => $roomCharges,
                    'room_charges_formatted' => '₱'.number_format($roomCharges, 2),
                    'extra_charges' => $extraCharges,
                    'extra_charges_formatted' => '₱'.number_format($extraCharges, 2),
                    'cancelled_at' => $b->updated_at ? SystemFormatService::formatDateTime($b->updated_at) : 'N/A',
                    'raw_time' => $b->updated_at ? Carbon::parse($b->updated_at)->timestamp : 0,
                ];
            });

        $cancelledReservations = Reservation::where('status', 'cancelled')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($res) use ($formatRoomDetails, $roomsMap) {
                $deposit = (float) ($res->deposit ?? 0);
                $roomInfo = $formatRoomDetails($res->selected_rooms);
                $start = $res->reservation_start_date ? Carbon::parse($res->reservation_start_date)->format('M d, Y') : null;
                $end = $res->reservation_end_date ? Carbon::parse($res->reservation_end_date)->format('M d, Y') : null;
                $dates = ($start && $end) ? "{$start} &ndash; {$end}" : ($start ?: 'N/A');

                $roomCharges = $deposit;
                $extraCharges = 0.0;
                $lostAmount = $deposit;

                if ($lostAmount <= 0) {
                    $roomIds = is_array($res->selected_rooms) ? $res->selected_rooms : (json_decode($res->selected_rooms, true) ?? []);
                    $isShort = ($res->stay_type === 'short_stay');
                    $calculatedRoomTotal = 0.0;
                    foreach ($roomIds as $rId) {
                        $rm = $roomsMap->get($rId) ?? $roomsMap->firstWhere('room_number', (string) $rId);
                        if ($rm) {
                            $calculatedRoomTotal += $isShort ? (float) $rm->short_stay_rate : (float) $rm->overnight_rate;
                        }
                    }
                    if ($calculatedRoomTotal > 0) {
                        $roomCharges = $calculatedRoomTotal;
                        $lostAmount = $calculatedRoomTotal;
                    }
                }

                return [
                    'id' => $res->id,
                    'type' => 'Reservation',
                    'guest_name' => $res->guest_name ?? 'Guest',
                    'room_number' => $roomInfo['numbers'],
                    'booked_rooms_details' => $roomInfo['details'],
                    'dates' => $dates,
                    'stay_type' => 'Reserved Stay',
                    'deposit' => $lostAmount,
                    'deposit_formatted' => '₱'.number_format($lostAmount, 2),
                    'lost_revenue' => $lostAmount,
                    'lost_revenue_formatted' => '₱'.number_format($lostAmount, 2),
                    'room_charges' => $roomCharges,
                    'room_charges_formatted' => '₱'.number_format($roomCharges, 2),
                    'extra_charges' => $extraCharges,
                    'extra_charges_formatted' => '₱'.number_format($extraCharges, 2),
                    'cancelled_at' => $res->updated_at ? SystemFormatService::formatDateTime($res->updated_at) : 'N/A',
                    'raw_time' => $res->updated_at ? Carbon::parse($res->updated_at)->timestamp : 0,
                ];
            });

        $cancellations = $cancelledReservations->concat($cancelledBookings)
            ->sortByDesc('raw_time')
            ->values();

        $lostRevenueTotal = (float) $cancellations->sum('lost_revenue');

        return response()->json([
            'due_guests' => $dueGuests,
            'due_guests_count' => $dueGuests->count(),
            'cancellations' => $cancellations,
            'cancellations_count' => $cancellations->count(),
            'lost_revenue_total' => $lostRevenueTotal,
            'lost_revenue_total_formatted' => '₱'.number_format($lostRevenueTotal, 2),
            'server_time' => $now->toIso8601String(),
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
