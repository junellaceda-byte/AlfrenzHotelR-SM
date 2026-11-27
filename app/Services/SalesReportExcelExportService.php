<?php

namespace App\Services;

use App\Models\Amenity;
use App\Models\AvailableAmenity;
use App\Models\Booking;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\MonthlyTarget;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SalesReportExcelExportService
{
    /**
     * Generate and download a fully styled OpenXML .xlsx Excel workbook.
     *
     * @param  array<string, mixed>  $filterParams
     * @param  array<string, string>  $colors
     */
    public function export(array $filterParams, array $colors): BinaryFileResponse
    {
        $data = $this->collectReportData($filterParams);
        $tempPath = $this->generateXlsxFile($data, $colors);

        $filename = 'Sales_Report_'.$data['period_file_slug'].'.xlsx';

        $response = new BinaryFileResponse($tempPath);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Gather all filtered Sales Reports data across all sections.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function collectReportData(array $params): array
    {
        $now = now()->setTimezone('Asia/Manila');
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        $timeframe = $params['timeframe'] ?? null;
        if (! isset($params['timeframe']) && ! isset($params['date']) && ! isset($params['month']) && ! isset($params['year'])) {
            $timeframe = 'today';
        }
        $selectedYear = (int) ($params['year'] ?? $currentYear);
        $monthInput = $params['month'] ?? (string) $currentMonth;
        $selectedDate = $params['date'] ?? null;

        if ($selectedDate && str_contains((string) $selectedDate, '/')) {
            try {
                $selectedDate = Carbon::createFromFormat('n/j/Y', (string) $selectedDate, 'Asia/Manila')->toDateString();
            } catch (\Throwable $e) {
                try {
                    $selectedDate = Carbon::parse((string) $selectedDate, 'Asia/Manila')->toDateString();
                } catch (\Throwable $e) {
                }
            }
        }

        $isSpecificDate = ! empty($selectedDate) && strtotime((string) $selectedDate) !== false;

        // Resolve range start and end based on timeframe / parameters
        if ($timeframe === 'today' || ($isSpecificDate && $timeframe === 'custom') || ($isSpecificDate && ! $timeframe)) {
            $dateToUse = ($timeframe === 'today') ? $now->toDateString() : $selectedDate;
            $rangeStart = Carbon::parse($dateToUse, 'Asia/Manila')->startOfDay();
            $rangeEnd = Carbon::parse($dateToUse, 'Asia/Manila')->endOfDay();
            $periodTitle = $rangeStart->format('M j, Y');
            $periodFileSlug = $rangeStart->format('M_j_Y');
            $filterName = ($timeframe === 'today') ? 'Today' : 'Custom ('.$rangeStart->format('n/j/Y').')';
        } elseif ($timeframe === 'weekly') {
            $rangeStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $rangeEnd = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $periodTitle = $rangeStart->format('M j').' – '.$rangeEnd->format('M j, Y').' (Current Week)';
            $periodFileSlug = 'Week_'.$rangeStart->format('M_j').'_to_'.$rangeEnd->format('M_j_Y');
            $filterName = 'Weekly';
        } elseif ($timeframe === 'yearly' || $monthInput === 'all') {
            $rangeStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
            $rangeEnd = $rangeStart->copy()->endOfYear();
            $periodTitle = 'Year '.$selectedYear;
            $periodFileSlug = 'Year_'.$selectedYear;
            $filterName = 'Yearly';
        } elseif ($timeframe === 'monthly' || (! empty($monthInput) && $monthInput !== 'all')) {
            $m = (int) ($monthInput ?: $currentMonth);
            $rangeStart = Carbon::create($selectedYear, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
            $rangeEnd = $rangeStart->copy()->endOfMonth();
            $periodTitle = $rangeStart->format('F Y');
            $periodFileSlug = $rangeStart->format('F_Y');
            $filterName = 'Monthly';
        } else {
            // Default: This Month
            $rangeStart = $now->copy()->startOfMonth();
            $rangeEnd = $now->copy()->endOfMonth();
            $periodTitle = $rangeStart->format('F Y');
            $periodFileSlug = $rangeStart->format('F_Y');
            $filterName = 'This Month';
        }

        // 1. Confirmed Payments & Paid Invoices
        $paymentLogs = PaymentLog::where('status', 'successful')
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($sub) use ($rangeStart, $rangeEnd) {
                        $sub->whereNull('paid_at')->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                    });
            })
            ->with(['invoice.booking.guest'])
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

        // ADR & Nights
        $totalRoomNights = 0;
        foreach ($paidInvoices as $inv) {
            $b = $inv->booking;
            if ($b) {
                $rc = is_array($b->booked_rooms) ? count($b->booked_rooms) : 1;
                $totalRoomNights += ($rc * ($b->nights ?: 1));
            } else {
                $totalRoomNights += 1;
            }
        }
        $adr = $totalRoomNights > 0 ? round($totalRoomCharges / $totalRoomNights, 2) : 0.00;
        $totalBookings = $paidInvoices->pluck('booking_id')->filter()->unique()->count() ?: $paidInvoices->count();

        // 2. Monthly Target
        $targetYear = (int) $rangeStart->format('Y');
        $targetMonth = (int) $rangeStart->format('n');
        $targetRecord = MonthlyTarget::where('year', $targetYear)->where('month', $targetMonth)->first();
        $monthlyTarget = $targetRecord ? (float) $targetRecord->target_amount : 150000.00;
        $targetProgress = $monthlyTarget > 0 ? min(100, round(($totalRevenue / $monthlyTarget) * 100, 1)) : 0;

        // 3. Cancellations & Lost Revenue
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

        // 4. Pending Payments
        $pendingInvoices = Invoice::where('status', 'unpaid')->with(['booking.guest'])->get();
        $pendingGuestsCount = $pendingInvoices->pluck('booking_id')->filter()->unique()->count() ?: $pendingInvoices->count();
        $pendingAmount = (float) $pendingInvoices->sum('grand_total');

        // 5. Occupancy Breakdown
        $totalHotelRooms = Room::count() ?: 20;
        $occupancyRows = [];

        if ($timeframe === 'today' || $isSpecificDate) {
            // Hourly blocks for single date
            $blocks = [
                ['12:00 AM - 04:00 AM', 0, 4],
                ['04:00 AM - 08:00 AM', 4, 8],
                ['08:00 AM - 12:00 PM', 8, 12],
                ['12:00 PM - 04:00 PM', 12, 16],
                ['04:00 PM - 08:00 PM', 16, 20],
                ['08:00 PM - 11:59 PM', 20, 24],
            ];
            foreach ($blocks as [$label, $startH, $endH]) {
                $bStart = $rangeStart->copy()->setTime($startH, 0, 0);
                $bEnd = ($endH === 24) ? $rangeStart->copy()->endOfDay() : $rangeStart->copy()->setTime($endH, 0, 0);
                $bRev = (float) PaymentLog::where('status', 'successful')
                    ->where(function ($q) use ($bStart, $bEnd) {
                        $q->whereBetween('paid_at', [$bStart, $bEnd])
                            ->orWhere(function ($sub) use ($bStart, $bEnd) {
                                $sub->whereNull('paid_at')->whereBetween('created_at', [$bStart, $bEnd]);
                            });
                    })->sum('amount_paid');

                $bOccCount = Booking::where('status', '!=', 'cancelled')
                    ->where('check_in_datetime', '<=', $bEnd)
                    ->where(function ($q) use ($bStart) {
                        $q->where('check_out_datetime', '>=', $bStart)
                            ->orWhere('status', 'checked_in');
                    })->count();
                $bOccRate = $totalHotelRooms > 0 ? min(100, round(($bOccCount / $totalHotelRooms) * 100)) : 0;

                $occupancyRows[] = [
                    'period' => $label,
                    'occupancy_rate' => $bOccRate,
                    'revenue' => $bRev,
                ];
            }
        } elseif ($timeframe === 'weekly') {
            // Days of the week
            for ($d = 0; $d < 7; $d++) {
                $dayStart = $rangeStart->copy()->addDays($d)->startOfDay();
                $dayEnd = $dayStart->copy()->endOfDay();
                $dRev = (float) PaymentLog::where('status', 'successful')
                    ->where(function ($q) use ($dayStart, $dayEnd) {
                        $q->whereBetween('paid_at', [$dayStart, $dayEnd])
                            ->orWhere(function ($sub) use ($dayStart, $dayEnd) {
                                $sub->whereNull('paid_at')->whereBetween('created_at', [$dayStart, $dayEnd]);
                            });
                    })->sum('amount_paid');

                $dOccCount = Booking::where('status', '!=', 'cancelled')
                    ->where('check_in_datetime', '<=', $dayEnd)
                    ->where(function ($q) use ($dayStart) {
                        $q->where('check_out_datetime', '>=', $dayStart)
                            ->orWhere('status', 'checked_in');
                    })->count();
                $dOccRate = $totalHotelRooms > 0 ? min(100, round(($dOccCount / $totalHotelRooms) * 100)) : 0;

                $occupancyRows[] = [
                    'period' => $dayStart->format('l (M j)'),
                    'occupancy_rate' => $dOccRate,
                    'revenue' => $dRev,
                ];
            }
        } elseif ($timeframe === 'yearly') {
            // 5 Years
            for ($y = $selectedYear - 4; $y <= $selectedYear; $y++) {
                $yStart = Carbon::create($y, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfYear();
                $yEnd = $yStart->copy()->endOfYear();
                $yRev = (float) PaymentLog::where('status', 'successful')
                    ->where(function ($q) use ($yStart, $yEnd) {
                        $q->whereBetween('paid_at', [$yStart, $yEnd])
                            ->orWhere(function ($sub) use ($yStart, $yEnd) {
                                $sub->whereNull('paid_at')->whereBetween('created_at', [$yStart, $yEnd]);
                            });
                    })->sum('amount_paid');
                if ($yRev == 0) {
                    $yRev = (float) Invoice::where('status', 'paid')->whereBetween('updated_at', [$yStart, $yEnd])->sum('grand_total');
                }
                $yCapacity = $totalHotelRooms * 365;
                $yOcc = $yCapacity > 0 ? min(100, round((Booking::where('status', '!=', 'cancelled')->whereBetween('created_at', [$yStart, $yEnd])->count() * 2 / $yCapacity) * 100)) : 0;

                $occupancyRows[] = [
                    'period' => (string) $y,
                    'occupancy_rate' => $yOcc,
                    'revenue' => $yRev,
                ];
            }
        } elseif ($timeframe === 'monthly') {
            // 12 Months
            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::create($selectedYear, $m, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
                $mEnd = $mStart->copy()->endOfMonth();
                $mRev = (float) PaymentLog::where('status', 'successful')
                    ->where(function ($q) use ($mStart, $mEnd) {
                        $q->whereBetween('paid_at', [$mStart, $mEnd])
                            ->orWhere(function ($sub) use ($mStart, $mEnd) {
                                $sub->whereNull('paid_at')->whereBetween('created_at', [$mStart, $mEnd]);
                            });
                    })->sum('amount_paid');
                if ($mRev == 0) {
                    $mRev = (float) Invoice::where('status', 'paid')->whereBetween('updated_at', [$mStart, $mEnd])->sum('grand_total');
                }
                $mOccupied = Booking::where('status', '!=', 'cancelled')->whereBetween('check_in_datetime', [$mStart, $mEnd])->count();
                $mCap = $totalHotelRooms * $mStart->daysInMonth;
                $mOcc = $mCap > 0 ? min(100, round(($mOccupied / $mCap) * 100)) : 0;

                $occupancyRows[] = [
                    'period' => $mStart->format('F'),
                    'occupancy_rate' => $mOcc,
                    'revenue' => $mRev,
                ];
            }
        } else {
            // This Month (Weeks 1 to 5)
            $monthForWeeks = Carbon::create($selectedYear, (int) $rangeStart->format('n'), 1, 0, 0, 0, 'Asia/Manila');
            for ($w = 1; $w <= 5; $w++) {
                $wStart = $monthForWeeks->copy()->addDays(($w - 1) * 7)->startOfDay();
                $wEnd = ($w === 5) ? $monthForWeeks->copy()->endOfMonth() : $wStart->copy()->addDays(6)->endOfDay();
                if ($wStart->month !== (int) $rangeStart->format('n')) {
                    continue;
                }
                $wRev = (float) PaymentLog::where('status', 'successful')
                    ->where(function ($q) use ($wStart, $wEnd) {
                        $q->whereBetween('paid_at', [$wStart, $wEnd])
                            ->orWhere(function ($sub) use ($wStart, $wEnd) {
                                $sub->whereNull('paid_at')->whereBetween('created_at', [$wStart, $wEnd]);
                            });
                    })->sum('amount_paid');
                if ($wRev == 0) {
                    $wRev = (float) Invoice::where('status', 'paid')->whereBetween('updated_at', [$wStart, $wEnd])->sum('grand_total');
                }
                $wOcc = Booking::where('status', '!=', 'cancelled')->whereBetween('check_in_datetime', [$wStart, $wEnd])->count();
                $daysInSpan = max(1, $wStart->diffInDays($wEnd) + 1);
                $occRate = ($totalHotelRooms * $daysInSpan) > 0 ? min(100, round(($wOcc / ($totalHotelRooms * $daysInSpan)) * 100)) : 0;

                $occupancyRows[] = [
                    'period' => 'Week '.$w.' ('.$wStart->format('M j').' – '.$wEnd->format('M j').')',
                    'occupancy_rate' => $occRate,
                    'revenue' => $wRev,
                ];
            }
        }

        // 6. Amenities Comparison (Ranked Best-Sellers)
        $paidInvoiceIds = $paidInvoices->pluck('id');
        $paidBookingIds = $paidInvoices->pluck('booking_id')->filter();

        $amenitiesList = AvailableAmenity::whereIn('invoice_id', $paidInvoiceIds)
            ->orWhere(function ($q) use ($paidBookingIds) {
                $q->whereIn('booking_id', $paidBookingIds);
            })->get();

        $amenitiesGrouped = $amenitiesList->groupBy('item_name')->map(function ($items, $name) {
            $count = (int) $items->sum('quantity') ?: $items->count();
            $rev = (float) $items->sum(function ($i) {
                return $i->total_amount ?: (($i->price ?: $i->unit_price) * ($i->quantity ?: 1));
            });

            return [
                'name' => $name,
                'count' => $count,
                'revenue' => $rev,
            ];
        })->values();

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
                })->values();
        }

        if ($amenitiesGrouped->isEmpty()) {
            $amenitiesGrouped = Amenity::where('offered', true)->get()->map(function ($a) {
                return [
                    'name' => $a->name,
                    'count' => 0,
                    'revenue' => 0.00,
                ];
            });
        }

        $amenitiesTotalRev = (float) $amenitiesGrouped->sum('revenue');
        $amenitiesRanked = $amenitiesGrouped->sortByDesc('count')->values()->map(function ($item, $idx) use ($amenitiesTotalRev) {
            $item['rank'] = $idx + 1;
            $item['share_percent'] = $amenitiesTotalRev > 0 ? round(($item['revenue'] / $amenitiesTotalRev) * 100, 1) : 0.0;

            return $item;
        });

        // 7. Confirmed Payment Invoice Rows
        $invoiceRows = $paidInvoices->map(function ($inv) {
            $booking = $inv->booking;
            $guest = $booking?->guest;
            $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'Guest';
            $rooms = $booking ? (is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? [])) : [];
            // Original: $roomStr = ! empty($rooms) ? implode(', ', $rooms) : 'N/A';
            $formattedRooms = array_filter(array_map(fn ($r) => Room::formatRoomNumber($r), $rooms), fn (string $v) => $v !== '');
            $roomStr = ! empty($formattedRooms) ? implode(', ', $formattedRooms) : 'N/A';
            $paidAt = $inv->updated_at ? Carbon::parse($inv->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d h:i A') : 'N/A';
            $advanceDeposit = (float) ($inv->advance_deposit ?? 0.00);

            return [
                'invoice_id' => $inv->id,
                'guest_name' => $guestName,
                'rooms' => $roomStr,
                'nights' => (int) ($booking?->nights ?: 1),
                'stay_type' => ucwords(str_replace('_', ' ', $booking?->stay_type ?? 'overnight')),
                'room_charges' => (float) $inv->room_charges,
                'extra_charges' => (float) $inv->extra_charges_total,
                'advance_deposit' => $advanceDeposit,
                'grand_total' => (float) $inv->grand_total,
                'status' => ucfirst($inv->status ?? 'paid'),
                'paid_at' => $paidAt,
            ];
        });

        // 8. Cancellations List
        $cancellationRows = collect();
        foreach ($cancelledReservations as $res) {
            $g = $res->guest;
            $name = $g ? trim(($g->fname ?? '').' '.($g->lname ?? '')) : 'Guest';
            $rooms = is_array($res->selected_rooms) ? $res->selected_rooms : (json_decode($res->selected_rooms, true) ?? []);
            $cancellationRows->push([
                'type' => 'Reservation',
                'ref_id' => 'RES-'.$res->id,
                'guest_name' => $name,
                'rooms' => implode(', ', $rooms),
                'cancelled_at' => $res->updated_at ? Carbon::parse($res->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d h:i A') : 'N/A',
                'lost_amount' => (float) ($res->deposit ?: (count($rooms) * 2200)),
            ]);
        }
        foreach ($cancelledBookings as $bk) {
            $g = $bk->guest;
            $name = $g ? trim(($g->fname ?? '').' '.($g->lname ?? '')) : 'Guest';
            $rooms = is_array($bk->booked_rooms) ? $bk->booked_rooms : (json_decode($bk->booked_rooms, true) ?? []);
            $cancellationRows->push([
                'type' => 'Booking',
                'ref_id' => 'BK-'.$bk->id,
                'guest_name' => $name,
                'rooms' => implode(', ', $rooms),
                'cancelled_at' => $bk->updated_at ? Carbon::parse($bk->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d h:i A') : 'N/A',
                'lost_amount' => (float) ($bk->deposit ?: (count($rooms) * 2200)),
            ]);
        }

        // 9. Pending Invoices List
        $pendingRows = $pendingInvoices->map(function ($pInv) {
            $b = $pInv->booking;
            $g = $b?->guest;
            $name = $g ? trim(($g->fname ?? '').' '.($g->lname ?? '')) : 'Guest';
            $rooms = $b ? (is_array($b->booked_rooms) ? $b->booked_rooms : (json_decode($b->booked_rooms, true) ?? [])) : [];

            return [
                'invoice_id' => $pInv->id,
                'guest_name' => $name,
                'rooms' => implode(', ', $rooms),
                'due_amount' => (float) $pInv->grand_total,
                'status' => 'Unpaid',
            ];
        });

        return [
            'period_title' => $periodTitle,
            'period_file_slug' => $periodFileSlug,
            'filter_name' => $filterName,
            'generated_at' => $now->format('Y-m-d h:i A'),
            'total_revenue' => $totalRevenue,
            'adr' => $adr,
            'total_bookings' => $totalBookings,
            'monthly_target' => $monthlyTarget,
            'target_progress' => $targetProgress,
            'total_room_charges' => $totalRoomCharges,
            'total_extra_charges' => $totalExtraCharges,
            'lost_revenue' => $lostRevenue,
            'cancellations_count' => $cancellationsCount,
            'pending_amount' => $pendingAmount,
            'pending_guests_count' => $pendingGuestsCount,
            'occupancy_rows' => $occupancyRows,
            'amenities_ranked' => $amenitiesRanked,
            'invoice_rows' => $invoiceRows,
            'cancellation_rows' => $cancellationRows,
            'pending_rows' => $pendingRows,
        ];
    }

    /**
     * Generate the OpenXML .xlsx file archive with custom color themes.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $colors
     */
    protected function generateXlsxFile(array $data, array $colors): string
    {
        // Fixed Vintage Brown & Beige System Palette (preserving original defaults in comments)
        // Original:
        // $headerBg = $this->sanitizeColor($colors['header_bg'] ?? '#443627', '443627');
        // $headerText = $this->sanitizeColor($colors['header_text'] ?? '#FFFFFF', 'FFFFFF');
        // $bodyText = $this->sanitizeColor($colors['body_text'] ?? '#212529', '212529');
        // $accentColor = $this->sanitizeColor($colors['accent_color'] ?? '#6A4435', '6A4435');
        // $rowBg = $this->sanitizeColor($colors['row_bg'] ?? '#F8F9FA', 'F8F9FA');
        // $summaryColor = $this->sanitizeColor($colors['summary_color'] ?? '#2E7D32', '2E7D32');
        $headerBg = $this->sanitizeColor($colors['header_bg'] ?? '#443627', '443627');
        $headerText = $this->sanitizeColor($colors['header_text'] ?? '#F5F7DF', 'F5F7DF');
        $bodyText = $this->sanitizeColor($colors['body_text'] ?? '#2C221A', '2C221A');
        $accentColor = $this->sanitizeColor($colors['accent_color'] ?? '#6A4435', '6A4435');
        $rowBg = $this->sanitizeColor($colors['row_bg'] ?? '#F5F7DF', 'F5F7DF');
        $summaryColor = $this->sanitizeColor($colors['summary_color'] ?? '#443627', '443627');

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create temporary xlsx zip archive.');
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            .'<Override PartName="/xl/charts/chart1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>'
            .'<Override PartName="/xl/charts/chart2.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>'
            .'<Override PartName="/xl/charts/chart3.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>'
            .'<Override PartName="/xl/charts/chart4.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>'
            .'<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Sales Summary" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Confirmed Payments" sheetId="2" r:id="rId2"/>'
            .'<sheet name="Cancellations &amp; Pending" sheetId="3" r:id="rId3"/>'
            .'</sheets>'
            .'</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml with Vintage Brown & Beige palette
        $styles = $this->buildStylesXml($headerBg, $headerText, $bodyText, $accentColor, $rowBg, $summaryColor);
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. Worksheets & Embedded Charts
        $sheet1Meta = [];
        $sheet1 = $this->buildSheet1Xml($data, $sheet1Meta);
        $sheet2 = $this->buildSheet2Xml($data);
        $sheet3 = $this->buildSheet3Xml($data);

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1);
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2);
        $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3);

        // 7. Sheet 1 Drawing & Embedded OpenXML Charts
        $sheet1Rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheet1Rels);

        $drawingRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart3.xml"/>'
            .'<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart4.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $drawingRels);

        $zip->addFromString('xl/drawings/drawing1.xml', $this->buildDrawing1Xml());
        $zip->addFromString('xl/charts/chart1.xml', $this->buildRevenueBreakdownChartXml($data, $sheet1Meta));
        $zip->addFromString('xl/charts/chart2.xml', $this->buildOccupancyTrendChartXml($data, $sheet1Meta));
        $zip->addFromString('xl/charts/chart3.xml', $this->buildAmenitiesComparisonChartXml($data, $sheet1Meta));
        $zip->addFromString('xl/charts/chart4.xml', $this->buildMonthlyTargetChartXml($data, $sheet1Meta));

        $zip->close();

        return $tempFile;
    }

    /**
     * Build the OpenXML styles definition using the customized colors.
     */
    protected function buildStylesXml(
        string $headerBg,
        string $headerText,
        string $bodyText,
        string $accentColor,
        string $rowBg,
        string $summaryColor
    ): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="3">'
            .'<numFmt numFmtId="164" formatCode="&quot;₱&quot;#,##0.00"/>'
            .'<numFmt numFmtId="165" formatCode="0.0%"/>'
            .'<numFmt numFmtId="166" formatCode="#,##0"/>'
            .'</numFmts>'
            .'<fonts count="6">'
            // 0: Normal Body Font
            .'<font><sz val="11"/><color rgb="FF'.$bodyText.'"/><name val="Calibri"/><family val="2"/></font>'
            // 1: Header Font (Bold, Header Text Color)
            .'<font><b/><sz val="11"/><color rgb="FF'.$headerText.'"/><name val="Calibri"/><family val="2"/></font>'
            // 2: Section Title Font (Bold, 14pt, Accent Color)
            .'<font><b/><sz val="14"/><color rgb="FF'.$accentColor.'"/><name val="Calibri"/><family val="2"/></font>'
            // 3: Sub-heading Font (Bold, 11pt, Accent Color)
            .'<font><b/><sz val="11"/><color rgb="FF'.$accentColor.'"/><name val="Calibri"/><family val="2"/></font>'
            // 4: Summary / Total Font (Bold, Summary Color)
            .'<font><b/><sz val="11"/><color rgb="FF'.$summaryColor.'"/><name val="Calibri"/><family val="2"/></font>'
            // 5: Report Main Header Font (Bold, 16pt, White)
            .'<font><b/><sz val="16"/><color rgb="FF'.$headerText.'"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="6">'
            // 0: None
            .'<fill><patternFill patternType="none"/></fill>'
            // 1: Gray125
            .'<fill><patternFill patternType="gray125"/></fill>'
            // 2: Header Background Fill
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF'.$headerBg.'"/></patternFill></fill>'
            // 3: Alternating Row Fill
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF'.$rowBg.'"/></patternFill></fill>'
            // 4: Accent Background Fill (Subtle Accent Tint)
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF'.$accentColor.'"/></patternFill></fill>'
            // 5: Summary Highlight Fill (Vintage Warm Beige #EFEABB)
            // Original: .'<fill><patternFill patternType="solid"><fgColor rgb="FF'.$rowBg.'"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFEABB"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="3">'
            // 0: None
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            // 1: Thin Cell Border (Warm Beige-Brown #C9B99F)
            .'<border>'
            .'<left style="thin"><color rgb="FFC9B99F"/></left>'
            .'<right style="thin"><color rgb="FFC9B99F"/></right>'
            .'<top style="thin"><color rgb="FFC9B99F"/></top>'
            .'<bottom style="thin"><color rgb="FFC9B99F"/></bottom>'
            .'</border>'
            // 2: Total Row Border (Top thin, bottom double)
            .'<border>'
            .'<left style="thin"><color rgb="FFC9B99F"/></left>'
            .'<right style="thin"><color rgb="FFC9B99F"/></right>'
            .'<top style="thin"><color rgb="FF'.$summaryColor.'"/></top>'
            .'<bottom style="double"><color rgb="FF'.$summaryColor.'"/></bottom>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'</cellStyleXfs>'
            .'<cellXfs count="13">'
            // 0: Default Normal
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: Table Column Header (Header Bg, Header Text, Border)
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // 2: Body Cell Normal (Border)
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            // 3: Body Cell Alternating (Row Bg, Border)
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            // 4: Currency Normal
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            // 5: Currency Alternating
            .'<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            // 6: Section Heading Bar (Accent Font)
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            // 7: Summary / Total Label (Font 4, Total Border)
            .'<xf numFmtId="0" fontId="4" fillId="5" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 8: Summary / Total Currency (Font 4, Total Border, Currency)
            .'<xf numFmtId="164" fontId="4" fillId="5" borderId="2" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 9: Main Title Banner (Font 5, Header Bg)
            .'<xf numFmtId="0" fontId="5" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 10: Percentage Normal
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            // 11: Percentage Alternating
            .'<xf numFmtId="165" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            // 12: Integer Count Normal
            .'<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * Sheet 1: Sales & Financial Summary (KPIs, Breakdown, Occupancy, Amenities)
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $meta
     */
    // Original: protected function buildSheet1Xml(array $data): string
    protected function buildSheet1Xml(array $data, array &$meta = []): string
    {
        $rows = [];
        $r = 1;

        // Banner Title
        $rows[] = '<row r="'.$r.'" ht="36" customHeight="1">'
            .'<c r="A'.$r.'" s="9" t="inlineStr"><is><t>ALFRENZO\'S HOTEL — SALES REPORT</t></is></c>'
            .'</row>';
        $r++;

        // Metadata
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>Reporting Period: '.$this->escapeXml($data['period_title']).' (Filter: '.$this->escapeXml($data['filter_name']).')</t></is></c>'
            .'</row>';
        $r++;
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="0" t="inlineStr"><is><t>Generated On: '.$this->escapeXml($data['generated_at']).' (Asia/Manila)</t></is></c>'
            .'</row>';
        $r += 2;

        // SECTION 1: KEY FINANCIAL METRICS
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>1. KEY FINANCIAL PERFORMANCE METRICS</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Metric Description</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Value / Amount</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Notes / Source</t></is></c>'
            .'</row>';
        $r++;

        $kpis = [
            ['Total Revenue', $data['total_revenue'], 'currency', 'Confirmed payments & settled invoices'],
            ['Average Daily Rate (ADR)', $data['adr'], 'currency', 'Room revenue divided by total room nights sold'],
            ['Total Bookings Count', $data['total_bookings'], 'number', 'Distinct paid/confirmed bookings in this period'],
            ['Monthly Target Goal', $data['monthly_target'], 'currency', 'Target configured for this period'],
            ['Target Progress', $data['target_progress'] / 100, 'percent', 'Progress towards the monthly revenue target'],
            ['Room Charges Total', $data['total_room_charges'], 'currency', 'Base accommodation fees collected'],
            ['Extra Charges & Add-ons', $data['total_extra_charges'], 'currency', 'Customizable amenities, extra beds & add-ons'],
            ['Lost Revenue (Cancellations)', $data['lost_revenue'], 'currency', 'Potential revenue lost from cancelled stays'],
            ['Total Cancellations Count', $data['cancellations_count'], 'number', 'Cancelled bookings and reservations'],
            ['Pending Unsettled Balances', $data['pending_amount'], 'currency', 'Unpaid invoices in Receptionist Billing'],
            ['Pending Guests Count', $data['pending_guests_count'], 'number', 'Guests with outstanding balances'],
        ];

        $meta['kpi_start_row'] = $r;
        foreach ($kpis as $idx => [$label, $val, $type, $notes]) {
            if ($label === 'Total Revenue') {
                $meta['kpi_revenue_row'] = $r;
            } elseif ($label === 'Monthly Target Goal') {
                $meta['kpi_target_row'] = $r;
            }
            $sStyle = ($idx % 2 === 0) ? 2 : 3;
            $vStyle = ($idx % 2 === 0) ? 4 : 5;
            if ($type === 'percent') {
                $vStyle = ($idx % 2 === 0) ? 10 : 11;
            } elseif ($type === 'number') {
                $vStyle = ($idx % 2 === 0) ? 12 : 12;
            }

            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($label).'</t></is></c>'
                .'<c r="B'.$r.'" s="'.$vStyle.'"><v>'.$val.'</v></c>'
                .'<c r="C'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($notes).'</t></is></c>'
                .'</row>';
            $r++;
        }
        $r += 2;

        // SECTION 2: REVENUE BREAKDOWN
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>2. REVENUE BREAKDOWN (ROOM RATES VS EXTRA CHARGES)</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Revenue Stream</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Amount (PHP)</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Share of Gross Revenue</t></is></c>'
            .'</row>';
        $r++;

        $totRev = max(0.01, $data['total_revenue']);
        $breakdownItems = [
            ['Base Room Charges', $data['total_room_charges'], ($data['total_room_charges'] / $totRev)],
            ['Extra Charges & Customizable Amenities', $data['total_extra_charges'], ($data['total_extra_charges'] / $totRev)],
        ];

        $meta['breakdown_start_row'] = $r;
        foreach ($breakdownItems as $idx => [$label, $amt, $share]) {
            $sStyle = ($idx % 2 === 0) ? 2 : 3;
            $vStyle = ($idx % 2 === 0) ? 4 : 5;
            $pStyle = ($idx % 2 === 0) ? 10 : 11;

            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($label).'</t></is></c>'
                .'<c r="B'.$r.'" s="'.$vStyle.'"><v>'.$amt.'</v></c>'
                .'<c r="C'.$r.'" s="'.$pStyle.'"><v>'.round($share, 4).'</v></c>'
                .'</row>';
            $r++;
        }
        $meta['breakdown_end_row'] = $r - 1;

        // Total Row
        $rows[] = '<row r="'.$r.'" ht="22" customHeight="1">'
            .'<c r="A'.$r.'" s="7" t="inlineStr"><is><t>Total Gross Revenue</t></is></c>'
            .'<c r="B'.$r.'" s="8"><v>'.$data['total_revenue'].'</v></c>'
            .'<c r="C'.$r.'" s="7" t="inlineStr"><is><t>100.0%</t></is></c>'
            .'</row>';
        $r += 3;

        // SECTION 3: OCCUPANCY & REVENUE TREND
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>3. OCCUPANCY &amp; REVENUE TREND DATA</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Time Period Interval</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Occupancy Rate (%)</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Period Revenue (PHP)</t></is></c>'
            .'</row>';
        $r++;

        $meta['occupancy_start_row'] = $r;
        foreach ($data['occupancy_rows'] as $idx => $occ) {
            $sStyle = ($idx % 2 === 0) ? 2 : 3;
            $pStyle = ($idx % 2 === 0) ? 10 : 11;
            $cStyle = ($idx % 2 === 0) ? 4 : 5;

            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($occ['period']).'</t></is></c>'
                .'<c r="B'.$r.'" s="'.$pStyle.'"><v>'.($occ['occupancy_rate'] / 100).'</v></c>'
                .'<c r="C'.$r.'" s="'.$cStyle.'"><v>'.$occ['revenue'].'</v></c>'
                .'</row>';
            $r++;
        }
        $meta['occupancy_end_row'] = max($meta['occupancy_start_row'], $r - 1);
        $r += 2;

        // SECTION 4: BEST-SELLING AMENITIES COMPARISON
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>4. AMENITIES COMPARISON &amp; BEST-SELLERS</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Rank</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Amenity Name</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Purchases / Quantity</t></is></c>'
            .'<c r="D'.$r.'" s="1" t="inlineStr"><is><t>Revenue Generated (PHP)</t></is></c>'
            .'<c r="E'.$r.'" s="1" t="inlineStr"><is><t>Share of Amenities Revenue</t></is></c>'
            .'</row>';
        $r++;

        $meta['amenities_start_row'] = $r;
        foreach ($data['amenities_ranked'] as $idx => $am) {
            $sStyle = ($idx % 2 === 0) ? 2 : 3;
            $cStyle = ($idx % 2 === 0) ? 4 : 5;
            $pStyle = ($idx % 2 === 0) ? 10 : 11;

            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>#'.$am['rank'].'</t></is></c>'
                .'<c r="B'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($am['name']).'</t></is></c>'
                .'<c r="C'.$r.'" s="'.$sStyle.'"><v>'.$am['count'].'</v></c>'
                .'<c r="D'.$r.'" s="'.$cStyle.'"><v>'.$am['revenue'].'</v></c>'
                .'<c r="E'.$r.'" s="'.$pStyle.'"><v>'.round($am['share_percent'] / 100, 4).'</v></c>'
                .'</row>';
            $r++;
        }
        $meta['amenities_end_row'] = max($meta['amenities_start_row'], $r - 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<cols>'
            .'<col min="1" max="1" width="38" customWidth="1"/>'
            .'<col min="2" max="2" width="26" customWidth="1"/>'
            .'<col min="3" max="3" width="45" customWidth="1"/>'
            .'<col min="4" max="4" width="26" customWidth="1"/>'
            .'<col min="5" max="5" width="26" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<drawing r:id="rId1"/>'
            .'</worksheet>';
    }

    /**
     * Sheet 2: Confirmed Payments & Detailed Invoices
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildSheet2Xml(array $data): string
    {
        $rows = [];
        $r = 1;

        $rows[] = '<row r="'.$r.'" ht="30" customHeight="1">'
            .'<c r="A'.$r.'" s="9" t="inlineStr"><is><t>CONFIRMED PAYMENTS &amp; INVOICE TRANSACTIONS</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>Period: '.$this->escapeXml($data['period_title']).' | Complete Verified Guest Billing Records</t></is></c>'
            .'</row>';
        $r += 2;

        $headers = [
            'Invoice #',
            'Guest Full Name',
            'Booked Room(s)',
            'Nights',
            'Stay Type',
            'Room Charges (PHP)',
            'Extra Charges (PHP)',
            'Advance Deposit (PHP)',
            'Grand Total (PHP)',
            'Payment Status',
            'Paid Date & Time',
        ];

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">';
        foreach ($headers as $colIdx => $hdr) {
            $colLetter = chr(65 + $colIdx);
            $rows[] = '<c r="'.$colLetter.$r.'" s="1" t="inlineStr"><is><t>'.$this->escapeXml($hdr).'</t></is></c>';
        }
        $rows[] = '</row>';
        $r++;

        $totalRoom = 0;
        $totalExtra = 0;
        $totalDeposit = 0;
        $totalGrand = 0;

        foreach ($data['invoice_rows'] as $idx => $inv) {
            $sStyle = ($idx % 2 === 0) ? 2 : 3;
            $cStyle = ($idx % 2 === 0) ? 4 : 5;

            $totalRoom += $inv['room_charges'];
            $totalExtra += $inv['extra_charges'];
            $totalDeposit += $inv['advance_deposit'];
            $totalGrand += $inv['grand_total'];

            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>#'.$inv['invoice_id'].'</t></is></c>'
                .'<c r="B'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($inv['guest_name']).'</t></is></c>'
                .'<c r="C'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($inv['rooms']).'</t></is></c>'
                .'<c r="D'.$r.'" s="'.$sStyle.'"><v>'.$inv['nights'].'</v></c>'
                .'<c r="E'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($inv['stay_type']).'</t></is></c>'
                .'<c r="F'.$r.'" s="'.$cStyle.'"><v>'.$inv['room_charges'].'</v></c>'
                .'<c r="G'.$r.'" s="'.$cStyle.'"><v>'.$inv['extra_charges'].'</v></c>'
                .'<c r="H'.$r.'" s="'.$cStyle.'"><v>'.$inv['advance_deposit'].'</v></c>'
                .'<c r="I'.$r.'" s="'.$cStyle.'"><v>'.$inv['grand_total'].'</v></c>'
                .'<c r="J'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($inv['status']).'</t></is></c>'
                .'<c r="K'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($inv['paid_at']).'</t></is></c>'
                .'</row>';
            $r++;
        }

        // Summary / Total Row
        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="7" t="inlineStr"><is><t>TOTALS</t></is></c>'
            .'<c r="B'.$r.'" s="7" t="inlineStr"><is><t>'.count($data['invoice_rows']).' Paid Invoices</t></is></c>'
            .'<c r="C'.$r.'" s="7"/>'
            .'<c r="D'.$r.'" s="7"/>'
            .'<c r="E'.$r.'" s="7"/>'
            .'<c r="F'.$r.'" s="8"><v>'.$totalRoom.'</v></c>'
            .'<c r="G'.$r.'" s="8"><v>'.$totalExtra.'</v></c>'
            .'<c r="H'.$r.'" s="8"><v>'.$totalDeposit.'</v></c>'
            .'<c r="I'.$r.'" s="8"><v>'.$totalGrand.'</v></c>'
            .'<c r="J'.$r.'" s="7"/>'
            .'<c r="K'.$r.'" s="7"/>'
            .'</row>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<cols>'
            .'<col min="1" max="1" width="14" customWidth="1"/>'
            .'<col min="2" max="2" width="28" customWidth="1"/>'
            .'<col min="3" max="3" width="18" customWidth="1"/>'
            .'<col min="4" max="4" width="10" customWidth="1"/>'
            .'<col min="5" max="5" width="16" customWidth="1"/>'
            .'<col min="6" max="6" width="22" customWidth="1"/>'
            .'<col min="7" max="7" width="22" customWidth="1"/>'
            .'<col min="8" max="8" width="24" customWidth="1"/>'
            .'<col min="9" max="9" width="22" customWidth="1"/>'
            .'<col min="10" max="10" width="16" customWidth="1"/>'
            .'<col min="11" max="11" width="24" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'</worksheet>';
    }

    /**
     * Sheet 3: Cancellations & Pending Payments
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildSheet3Xml(array $data): string
    {
        $rows = [];
        $r = 1;

        // Section A: Cancellations
        $rows[] = '<row r="'.$r.'" ht="30" customHeight="1">'
            .'<c r="A'.$r.'" s="9" t="inlineStr"><is><t>CANCELLATIONS &amp; PENDING BALANCES</t></is></c>'
            .'</row>';
        $r += 2;

        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>1. CANCELLED RESERVATIONS &amp; BOOKINGS (LOST REVENUE)</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Record Type</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Reference ID</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Guest Name</t></is></c>'
            .'<c r="D'.$r.'" s="1" t="inlineStr"><is><t>Room(s)</t></is></c>'
            .'<c r="E'.$r.'" s="1" t="inlineStr"><is><t>Cancelled Date</t></is></c>'
            .'<c r="F'.$r.'" s="1" t="inlineStr"><is><t>Deposit / Lost Revenue (PHP)</t></is></c>'
            .'</row>';
        $r++;

        if ($data['cancellation_rows']->isEmpty()) {
            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="2" t="inlineStr"><is><t>No cancelled stays recorded for this period.</t></is></c>'
                .'</row>';
            $r++;
        } else {
            foreach ($data['cancellation_rows'] as $idx => $cRow) {
                $sStyle = ($idx % 2 === 0) ? 2 : 3;
                $cStyle = ($idx % 2 === 0) ? 4 : 5;

                $rows[] = '<row r="'.$r.'">'
                    .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($cRow['type']).'</t></is></c>'
                    .'<c r="B'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($cRow['ref_id']).'</t></is></c>'
                    .'<c r="C'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($cRow['guest_name']).'</t></is></c>'
                    .'<c r="D'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($cRow['rooms']).'</t></is></c>'
                    .'<c r="E'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($cRow['cancelled_at']).'</t></is></c>'
                    .'<c r="F'.$r.'" s="'.$cStyle.'"><v>'.$cRow['lost_amount'].'</v></c>'
                    .'</row>';
                $r++;
            }
        }
        $r += 2;

        // Section B: Pending Unpaid Invoices
        $rows[] = '<row r="'.$r.'">'
            .'<c r="A'.$r.'" s="6" t="inlineStr"><is><t>2. UNSETTLED / PENDING BALANCES (RECEPTIONIST BILLING)</t></is></c>'
            .'</row>';
        $r++;

        $rows[] = '<row r="'.$r.'" ht="24" customHeight="1">'
            .'<c r="A'.$r.'" s="1" t="inlineStr"><is><t>Invoice #</t></is></c>'
            .'<c r="B'.$r.'" s="1" t="inlineStr"><is><t>Guest Name</t></is></c>'
            .'<c r="C'.$r.'" s="1" t="inlineStr"><is><t>Room(s)</t></is></c>'
            .'<c r="D'.$r.'" s="1" t="inlineStr"><is><t>Status</t></is></c>'
            .'<c r="E'.$r.'" s="1" t="inlineStr"><is><t>Due Amount (PHP)</t></is></c>'
            .'</row>';
        $r++;

        if ($data['pending_rows']->isEmpty()) {
            $rows[] = '<row r="'.$r.'">'
                .'<c r="A'.$r.'" s="2" t="inlineStr"><is><t>All guest balances are settled.</t></is></c>'
                .'</row>';
            $r++;
        } else {
            foreach ($data['pending_rows'] as $idx => $pRow) {
                $sStyle = ($idx % 2 === 0) ? 2 : 3;
                $cStyle = ($idx % 2 === 0) ? 4 : 5;

                $rows[] = '<row r="'.$r.'">'
                    .'<c r="A'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>#'.$pRow['invoice_id'].'</t></is></c>'
                    .'<c r="B'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($pRow['guest_name']).'</t></is></c>'
                    .'<c r="C'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($pRow['rooms']).'</t></is></c>'
                    .'<c r="D'.$r.'" s="'.$sStyle.'" t="inlineStr"><is><t>'.$this->escapeXml($pRow['status']).'</t></is></c>'
                    .'<c r="E'.$r.'" s="'.$cStyle.'"><v>'.$pRow['due_amount'].'</v></c>'
                    .'</row>';
                $r++;
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<cols>'
            .'<col min="1" max="1" width="16" customWidth="1"/>'
            .'<col min="2" max="2" width="22" customWidth="1"/>'
            .'<col min="3" max="3" width="28" customWidth="1"/>'
            .'<col min="4" max="4" width="18" customWidth="1"/>'
            .'<col min="5" max="5" width="24" customWidth="1"/>'
            .'<col min="6" max="6" width="26" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'</worksheet>';
    }

    /**
     * Build the DrawingML container positioning all 4 embedded charts on Sheet 1 (Cols G-M).
     */
    protected function buildDrawing1Xml(): string
    {
        $charts = [
            ['rId1', 1, 'Revenue Breakdown Chart', 4, 17],
            ['rId2', 2, 'Occupancy & Revenue Trend Chart', 19, 34],
            ['rId3', 3, 'Amenities Comparison Chart', 36, 51],
            ['rId4', 4, 'Monthly Target Progress Chart', 53, 66],
        ];

        $anchors = '';
        foreach ($charts as [$rId, $id, $name, $fromRow, $toRow]) {
            $anchors .= '<xdr:twoCellAnchor>'
                .'<xdr:from><xdr:col>6</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.$fromRow.'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
                .'<xdr:to><xdr:col>12</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.$toRow.'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                .'<xdr:graphicFrame macro="">'
                .'<xdr:nvGraphicFramePr>'
                .'<xdr:cNvPr id="'.$id.'" name="'.$this->escapeXml($name).'"/>'
                .'<xdr:cNvGraphicFramePr/>'
                .'</xdr:nvGraphicFramePr>'
                .'<xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm>'
                .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">'
                .'<c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="'.$rId.'"/>'
                .'</a:graphicData></a:graphic>'
                .'</xdr:graphicFrame>'
                .'<xdr:clientData/>'
                .'</xdr:twoCellAnchor>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/main">'
            .$anchors
            .'</xdr:wsDr>';
    }

    /**
     * Chart 1: Revenue Breakdown (Base Room Charges vs Extra Charges)
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $meta
     */
    protected function buildRevenueBreakdownChartXml(array $data, array $meta): string
    {
        $start = $meta['breakdown_start_row'] ?? 22;
        $end = $meta['breakdown_end_row'] ?? 23;

        $categories = ['Base Room Charges', 'Extra Charges & Amenities'];
        $values = [(float) $data['total_room_charges'], (float) $data['total_extra_charges']];

        return $this->buildBarChartXml(
            title: 'Revenue Breakdown: Room Rates vs Extra Charges (PHP)',
            seriesTitle: 'Amount (PHP)',
            catFormula: "'Sales Summary'!\$A\${$start}:\$A\${$end}",
            valFormula: "'Sales Summary'!\$B\${$start}:\$B\${$end}",
            categories: $categories,
            values: $values,
            barDir: 'col',
            seriesColor: '443627',
            numFormat: '&quot;₱&quot;#,##0'
        );
    }

    /**
     * Chart 2: Occupancy & Revenue Trend
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $meta
     */
    protected function buildOccupancyTrendChartXml(array $data, array $meta): string
    {
        $start = $meta['occupancy_start_row'] ?? 28;
        $end = $meta['occupancy_end_row'] ?? $start;

        $occRows = $data['occupancy_rows'] ?? [];
        $categories = array_map(fn (array $row) => (string) $row['period'], $occRows);
        $values = array_map(fn (array $row) => (float) $row['revenue'], $occRows);

        if (empty($categories)) {
            $categories = ['No Data'];
            $values = [0.0];
        }

        return $this->buildBarChartXml(
            title: 'Revenue Trend ('.$data['period_title'].')',
            seriesTitle: 'Period Revenue (PHP)',
            catFormula: "'Sales Summary'!\$A\${$start}:\$A\${$end}",
            valFormula: "'Sales Summary'!\$C\${$start}:\$C\${$end}",
            categories: $categories,
            values: $values,
            barDir: 'col',
            seriesColor: '6A4435',
            numFormat: '&quot;₱&quot;#,##0'
        );
    }

    /**
     * Chart 3: Amenities Comparison (Horizontal Bar Chart)
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $meta
     */
    protected function buildAmenitiesComparisonChartXml(array $data, array $meta): string
    {
        $start = $meta['amenities_start_row'] ?? 36;
        $end = $meta['amenities_end_row'] ?? $start;

        $amRows = collect($data['amenities_ranked'] ?? [])->values()->all();
        $categories = array_map(fn (array $row) => (string) $row['name'], $amRows);
        $values = array_map(fn (array $row) => (float) $row['revenue'], $amRows);

        if (empty($categories)) {
            $categories = ['No Amenities'];
            $values = [0.0];
        }

        return $this->buildBarChartXml(
            title: 'Amenities Comparison (Revenue PHP)',
            seriesTitle: 'Amenities Revenue (PHP)',
            catFormula: "'Sales Summary'!\$B\${$start}:\$B\${$end}",
            valFormula: "'Sales Summary'!\$D\${$start}:\$D\${$end}",
            categories: $categories,
            values: $values,
            barDir: 'bar',
            seriesColor: '734F46',
            numFormat: '&quot;₱&quot;#,##0'
        );
    }

    /**
     * Chart 4: Monthly Target Goal vs Total Revenue
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $meta
     */
    protected function buildMonthlyTargetChartXml(array $data, array $meta): string
    {
        $revRow = $meta['kpi_revenue_row'] ?? 7;
        $targetRow = $meta['kpi_target_row'] ?? 10;

        $categories = ['Total Revenue', 'Monthly Target Goal'];
        $values = [(float) $data['total_revenue'], (float) $data['monthly_target']];

        return $this->buildBarChartXml(
            title: 'Monthly Target vs Revenue ('.$data['target_progress'].'% Achieved)',
            seriesTitle: 'PHP Amount',
            catFormula: "'Sales Summary'!\$A\${$revRow},'Sales Summary'!\$A\${$targetRow}",
            valFormula: "'Sales Summary'!\$B\${$revRow},'Sales Summary'!\$B\${$targetRow}",
            categories: $categories,
            values: $values,
            barDir: 'bar',
            seriesColor: 'BF9B7A',
            numFormat: '&quot;₱&quot;#,##0'
        );
    }

    /**
     * Generate a complete ECMA-376 OpenXML Bar/Column Chart styled with the Vintage Brown & Beige palette.
     *
     * @param  array<int, string>  $categories
     * @param  array<int, float>  $values
     */
    protected function buildBarChartXml(
        string $title,
        string $seriesTitle,
        string $catFormula,
        string $valFormula,
        array $categories,
        array $values,
        string $barDir = 'col',
        string $seriesColor = '443627',
        string $numFormat = 'General'
    ): string {
        $count = count($categories);
        $catPts = '';
        $valPts = '';
        foreach ($categories as $i => $cat) {
            $catPts .= '<c:pt idx="'.$i.'"><c:v>'.$this->escapeXml($cat).'</c:v></c:pt>';
            $valPts .= '<c:pt idx="'.$i.'"><c:v>'.($values[$i] ?? 0).'</c:v></c:pt>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<c:lang val="en-US"/>'
            .'<c:spPr>'
            .'<a:solidFill><a:srgbClr val="F5F7DF"/></a:solidFill>'
            .'<a:ln w="12700"><a:solidFill><a:srgbClr val="6A4435"/></a:solidFill></a:ln>'
            .'</c:spPr>'
            .'<c:chart>'
            .'<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="1200" b="1"><a:solidFill><a:srgbClr val="443627"/></a:solidFill></a:defRPr></a:pPr><a:r><a:rPr lang="en-US" sz="1200" b="1"><a:solidFill><a:srgbClr val="443627"/></a:solidFill></a:rPr><a:t>'.$this->escapeXml($title).'</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title>'
            .'<c:autoTitleDeleted val="0"/>'
            .'<c:plotArea>'
            .'<c:layout/>'
            .'<c:barChart>'
            .'<c:barDir val="'.$barDir.'"/>'
            .'<c:grouping val="clustered"/>'
            .'<c:varyColors val="0"/>'
            .'<c:ser>'
            .'<c:idx val="0"/>'
            .'<c:order val="0"/>'
            .'<c:tx><c:v>'.$this->escapeXml($seriesTitle).'</c:v></c:tx>'
            .'<c:spPr>'
            .'<a:solidFill><a:srgbClr val="'.$seriesColor.'"/></a:solidFill>'
            .'<a:ln w="6350"><a:solidFill><a:srgbClr val="443627"/></a:solidFill></a:ln>'
            .'</c:spPr>'
            .'<c:cat><c:strRef><c:f>'.$this->escapeXml($catFormula).'</c:f><c:strCache><c:ptCount val="'.$count.'"/>'.$catPts.'</c:strCache></c:strRef></c:cat>'
            .'<c:val><c:numRef><c:f>'.$this->escapeXml($valFormula).'</c:f><c:numCache><c:formatCode>'.$numFormat.'</c:formatCode><c:ptCount val="'.$count.'"/>'.$valPts.'</c:numCache></c:numRef></c:val>'
            .'</c:ser>'
            .'<c:axId val="101"/><c:axId val="102"/>'
            .'</c:barChart>'
            .'<c:catAx><c:axId val="101"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="'.($barDir === 'bar' ? 'l' : 'b').'"/><c:tickLblPos val="nextTo"/><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="900"><a:solidFill><a:srgbClr val="443627"/></a:solidFill></a:defRPr></a:pPr></a:p></c:txPr><c:crossAx val="102"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/></c:catAx>'
            .'<c:valAx><c:axId val="102"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="'.($barDir === 'bar' ? 'b' : 'l').'"/><c:majorGridlines><c:spPr><a:ln w="6350"><a:solidFill><a:srgbClr val="D8D3B8"/></a:solidFill></a:ln></c:spPr></c:majorGridlines><c:numFmt formatCode="'.$numFormat.'" sourceLinked="0"/><c:tickLblPos val="nextTo"/><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="900"><a:solidFill><a:srgbClr val="443627"/></a:solidFill></a:defRPr></a:pPr></a:p></c:txPr><c:crossAx val="101"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx>'
            .'<c:spPr><a:solidFill><a:srgbClr val="FDFEF7"/></a:solidFill></c:spPr>'
            .'</c:plotArea>'
            .'<c:legend><c:legendPos val="b"/><c:overlay val="0"/></c:legend>'
            .'<c:plotVisOnly val="1"/>'
            .'</c:chart>'
            .'</c:chartSpace>';
    }

    /**
     * Sanitize color string into a clean 6-character hex value.
     */
    protected function sanitizeColor(?string $hex, string $default): string
    {
        if (! $hex) {
            return $default;
        }

        $clean = strtoupper(trim(str_replace('#', '', $hex)));
        if (preg_match('/^[0-9A-F]{6}$/', $clean)) {
            return $clean;
        }

        return $default;
    }

    /**
     * Escape XML special characters.
     */
    protected function escapeXml(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
