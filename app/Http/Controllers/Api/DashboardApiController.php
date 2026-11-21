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
                'checkout_time' => $checkOut->format('h:i A'),
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
                $startFormatted = $block->start_date->format('n/j/Y, g:i A');
                $endFormatted = $block->end_date->format('n/j/Y, g:i A');
                $roomBlockingPeriods[$room->id] = "Start: {$startFormatted} and End: {$endFormatted}";
            } elseif ($booking) {
                $status = 'occupied';
                $checkIn = Carbon::parse($booking->check_in_datetime);
                $checkOut = Carbon::parse($booking->check_out_datetime);
                $roomCheckouts[$room->id] = $checkOut->toIso8601String();
                $roomTimes[$room->id] = [
                    'in' => $checkIn->format('g:i A'),
                    'out' => $checkOut->format('g:i A'),
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
                        $roomReservationArrivals[$room->id] = $arrivalStart->format('M j, Y g:i A');
                    } elseif ($reservedBooking) {
                        $arrivalStart = Carbon::parse($reservedBooking->check_in_datetime);
                        $roomReservationArrivals[$room->id] = $arrivalStart->format('M j, Y g:i A');
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
            $timeStr = $b->check_in_datetime ? Carbon::parse($b->check_in_datetime)->format('g:i A') : '';

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
            $timeStr = $time ? Carbon::parse($time)->format('g:i A') : '';

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
        $selectedDate = $request->input('date'); // 'YYYY-MM-DD' or null

        // Date range resolution
        $isSpecificDate = ! empty($selectedDate) && strtotime((string) $selectedDate) !== false;
        $isAllMonths = ($monthInput === 'all');
        $selectedMonth = $isAllMonths ? null : (int) $monthInput;

        if ($isSpecificDate) {
            $rangeStart = Carbon::parse($selectedDate, 'Asia/Manila')->startOfDay();
            $rangeEnd = Carbon::parse($selectedDate, 'Asia/Manila')->endOfDay();
            $periodTitle = $rangeStart->format('M j, Y');
            $revenueCardTitle = 'Total Revenue ('.$rangeStart->format('M j').')';
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
        $targetYear = $selectedYear;
        $targetMonth = $selectedMonth ?: $currentMonth;
        $targetRecord = MonthlyTarget::where('year', $targetYear)->where('month', $targetMonth)->first();
        $monthlyTargetAmount = $targetRecord ? (float) $targetRecord->target_amount : 150000.00;

        $monthTargetStart = Carbon::create($targetYear, $targetMonth, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        $monthTargetEnd = $monthTargetStart->copy()->endOfMonth();
        $targetMonthRevenue = (float) PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($monthTargetStart, $monthTargetEnd) {
                $q->whereBetween('paid_at', [$monthTargetStart, $monthTargetEnd])
                    ->orWhere(function ($sub) use ($monthTargetStart, $monthTargetEnd) {
                        $sub->whereNull('paid_at')->whereBetween('created_at', [$monthTargetStart, $monthTargetEnd]);
                    });
            })
            ->sum('amount_paid');
        if ($targetMonthRevenue == 0) {
            $targetMonthRevenue = (float) Invoice::where('status', 'paid')
                ->whereBetween('updated_at', [$monthTargetStart, $monthTargetEnd])
                ->sum('grand_total');
        }
        $monthlyTargetProgress = $monthlyTargetAmount > 0
            ? min(100, round(($targetMonthRevenue / $monthlyTargetAmount) * 100))
            : 0;

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

        // 8. Amenities Comparison (Ranked Horizontal Bar Chart)
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
            'lost_revenue' => $lostRevenue,
            'lost_revenue_formatted' => '-₱'.number_format($lostRevenue, 2),
            'cancellations_count' => $cancellationsCount,
            'pending_amount' => $pendingAmount,
            'pending_amount_formatted' => '₱'.number_format($pendingAmount, 2),
            'pending_guests_count' => $pendingGuestsCount,
            'occupancy_trend' => [
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
                'subtitle' => $periodTitle,
                'items' => $amenitiesRanked,
            ],
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
            'target_amount' => 'required|numeric|min:1',
        ]);

        $target = MonthlyTarget::updateOrCreate(
            [
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
            ],
            [
                'target_amount' => (float) $validated['target_amount'],
            ]
        );

        $monthStart = Carbon::create((int) $target->year, (int) $target->month, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
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

        $targetAmount = (float) $target->target_amount;
        $progressPercent = $targetAmount > 0 ? min(100, round(($monthRevenue / $targetAmount) * 100)) : 0;

        return response()->json([
            'status' => 'success',
            'message' => 'Monthly target updated successfully.',
            'target' => $target,
            'target_formatted' => '₱'.number_format($targetAmount, 2),
            'month_revenue' => $monthRevenue,
            'month_revenue_formatted' => '₱'.number_format($monthRevenue, 2),
            'progress_percent' => $progressPercent,
        ]);
    }

    /**
     * Admin Overview notifications: due checkouts and cancellation revenue losses.
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

        // 1. Due checkout guests (same logic as Receptionist notifications)
        $dueInvoices = Invoice::with('booking.guest')
            ->where('status', 'unpaid')
            ->whereHas('booking', function ($q) use ($now) {
                $q->where('status', 'checked_in')
                    ->where('check_out_datetime', '<=', $now->copy()->addMinutes(30));
            })
            ->get()
            ->sortBy(fn ($invoice) => $invoice->booking->check_out_datetime);

        $dueGuests = $dueInvoices->map(function ($invoice) use ($now, $formatRoomDetails) {
            $checkOut = Carbon::parse($invoice->booking->check_out_datetime);
            $roomInfo = $formatRoomDetails($invoice->booking->booked_rooms);

            return [
                'name' => trim(($invoice->booking->guest->fname ?? 'Guest').' '.($invoice->booking->guest->lname ?? '')),
                'room_number' => $roomInfo['numbers'],
                'booked_rooms_details' => $roomInfo['details'],
                'stay_type' => ucwords(str_replace('_', ' ', $invoice->booking->stay_type ?? 'overnight')),
                'check_in_time' => $invoice->booking->check_in_datetime ? Carbon::parse($invoice->booking->check_in_datetime)->format('M d, Y h:i A') : 'N/A',
                'checkout_time' => $checkOut->format('h:i A'),
                'is_overdue' => $checkOut->lte($now),
                'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
                'total_amount' => (float) ($invoice->grand_total ?? $invoice->booking->total_amount ?? 0),
                'total_amount_formatted' => '₱'.number_format((float) ($invoice->grand_total ?? $invoice->booking->total_amount ?? 0), 2),
            ];
        })->values();

        // 2. Cancellation revenue loss (Reservations & Bookings cancelled)
        $cancelledReservations = Reservation::where('status', 'cancelled')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($res) use ($formatRoomDetails) {
                $deposit = (float) ($res->deposit ?? 0);
                $roomInfo = $formatRoomDetails($res->selected_rooms);
                $start = $res->reservation_start_date ? Carbon::parse($res->reservation_start_date)->format('M d, Y') : null;
                $end = $res->reservation_end_date ? Carbon::parse($res->reservation_end_date)->format('M d, Y') : null;
                $dates = ($start && $end) ? "{$start} &ndash; {$end}" : ($start ?: 'N/A');

                return [
                    'id' => $res->id,
                    'type' => 'Reservation',
                    'guest_name' => $res->guest_name ?? 'Guest',
                    'room_number' => $roomInfo['numbers'],
                    'booked_rooms_details' => $roomInfo['details'],
                    'dates' => $dates,
                    'stay_type' => 'Reserved Stay',
                    'deposit' => $deposit,
                    'deposit_formatted' => '₱'.number_format($deposit, 2),
                    'cancelled_at' => $res->updated_at ? Carbon::parse($res->updated_at)->setTimezone('Asia/Manila')->format('M d, Y h:i A') : 'N/A',
                    'raw_time' => $res->updated_at ? Carbon::parse($res->updated_at)->timestamp : 0,
                ];
            });

        $cancelledBookings = Booking::with('guest')
            ->where('status', 'cancelled')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($b) use ($formatRoomDetails) {
                $deposit = (float) ($b->deposit ?? 0);
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
                    'deposit' => $deposit,
                    'deposit_formatted' => '₱'.number_format($deposit, 2),
                    'cancelled_at' => $b->updated_at ? Carbon::parse($b->updated_at)->setTimezone('Asia/Manila')->format('M d, Y h:i A') : 'N/A',
                    'raw_time' => $b->updated_at ? Carbon::parse($b->updated_at)->timestamp : 0,
                ];
            });

        $cancellations = $cancelledReservations->concat($cancelledBookings)
            ->sortByDesc('raw_time')
            ->values();

        $lostRevenueTotal = (float) $cancellations->sum('deposit');

        return response()->json([
            'due_guests' => $dueGuests,
            'due_guests_count' => $dueGuests->count(),
            'cancellations' => $cancellations,
            'cancellations_count' => $cancellations->count(),
            'lost_revenue_total' => $lostRevenueTotal,
            'lost_revenue_total_formatted' => '₱'.number_format($lostRevenueTotal, 2),
            'server_time' => $now->toIso8601String(),
        ]);
    }
}
