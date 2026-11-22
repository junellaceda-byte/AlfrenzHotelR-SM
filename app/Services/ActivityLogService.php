<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Resolve the current receptionist staff ID from session or request.
     */
    public static function currentStaffId(): ?int
    {
        $id = session('staff_id') ?? request('staff_id') ?? session('receptionist_id');

        if ($id) {
            return (int) $id;
        }

        // If auth user is a Staff instance
        $user = auth()->user();
        if ($user instanceof Staff) {
            return (int) $user->id;
        }

        return null;
    }

    /**
     * Helper to format room IDs into a human-readable string like "Room 01, 02".
     */
    public static function formatRooms(mixed $roomIds): string
    {
        if (empty($roomIds)) {
            return 'N/A';
        }

        if (is_string($roomIds)) {
            $decoded = json_decode($roomIds, true);
            $roomIds = is_array($decoded) ? $decoded : [$roomIds];
        }

        $formatted = collect((array) $roomIds)->map(function ($r) {
            if (is_numeric($r)) {
                $room = Room::find($r);
                $num = $room ? $room->room_number : $r;

                return str_pad($num, 2, '0', STR_PAD_LEFT);
            }

            return (string) $r;
        });

        return 'Room '.$formatted->implode(', ');
    }

    /**
     * 1. Record successfully processed payment.
     */
    public static function logPayment(PaymentLog $paymentLog, Invoice $invoice, ?int $staffId = null): ActivityLog
    {
        $staffId = $staffId ?? static::currentStaffId();
        $booking = $invoice->booking;
        $guest = $booking?->guest;
        $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'Walk-in Guest';
        $roomsStr = static::formatRooms($booking?->booked_rooms);

        $method = $paymentLog->payment_method ?? 'cash';
        $ref = $paymentLog->reference_number;
        $amount = (float) $paymentLog->amount_paid;

        // If staff is resolved, ensure processed_by on payment_log is set
        if ($staffId && empty($paymentLog->processed_by)) {
            $staff = Staff::find($staffId);
            if ($staff) {
                $paymentLog->update(['processed_by' => trim($staff->first_name.' '.$staff->last_name)]);
            }
        }

        return ActivityLog::create([
            'staff_id' => $staffId,
            'activity_type' => 'payment',
            'action' => 'payment_processed',
            'subject_type' => PaymentLog::class,
            'subject_id' => $paymentLog->id,
            'guest_name' => $guestName,
            'room_number' => $roomsStr,
            'booking_type' => $booking?->stay_type ?? 'overnight',
            'amount' => $amount,
            'payment_method' => $method,
            'reference_number' => $ref,
            'description' => 'Processed payment of ₱'.number_format($amount, 2)." for Invoice #{$invoice->invoice_number} ({$guestName}, {$roomsStr})",
            'details' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'booking_id' => $booking?->id,
                'payment_log_id' => $paymentLog->id,
                'amount_paid' => $amount,
                'payment_method' => $method,
                'reference_number' => $ref,
                'room_charges' => (float) $invoice->room_charges,
                'extra_charges_total' => (float) $invoice->extra_charges_total,
                'grand_total' => (float) $invoice->grand_total,
            ],
            'created_at' => $paymentLog->paid_at ?? now(),
        ]);
    }

    /**
     * 2. Record cancelled booking or reservation.
     */
    public static function logCancellation(Booking|Reservation $model, ?int $staffId = null, ?string $reason = null): ActivityLog
    {
        $staffId = $staffId ?? static::currentStaffId();

        if ($model instanceof Reservation) {
            $guestName = $model->guest_name ?? 'Guest';
            $roomsStr = static::formatRooms($model->selected_rooms);
            $action = 'reservation_cancelled';
            $typeLabel = 'Reservation';
            $stayType = $model->stay_type ?? 'reservation';
            $deposit = (float) ($model->deposit ?? 0);
            $refNumber = $model->reference_number;
        } else {
            $guest = $model->guest;
            $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'Guest';
            $roomsStr = static::formatRooms($model->booked_rooms);
            $action = 'booking_cancelled';
            $typeLabel = 'Booking';
            $stayType = $model->stay_type ?? 'overnight';
            $deposit = (float) ($model->deposit ?? 0);
            $refNumber = $model->reference_number;
        }

        return ActivityLog::create([
            'staff_id' => $staffId,
            'activity_type' => 'cancellation',
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'guest_name' => $guestName,
            'room_number' => $roomsStr,
            'booking_type' => $stayType,
            'amount' => $deposit,
            'payment_method' => $model->payment_method ?? null,
            'reference_number' => $refNumber,
            'description' => "Cancelled {$typeLabel} #{$model->id} for {$guestName} ({$roomsStr})",
            'details' => [
                'model_type' => $typeLabel,
                'id' => $model->id,
                'guest_name' => $guestName,
                'room_number' => $roomsStr,
                'deposit' => $deposit,
                'reason' => $reason,
                'cancelled_at' => now()->toIso8601String(),
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * 3. Record overdue booking as System-Generated.
     */
    public static function logOverdue(Booking $booking): ?ActivityLog
    {
        // Prevent duplicate overdue logs for the same booking
        $existing = ActivityLog::where('activity_type', 'overdue')
            ->where('subject_type', Booking::class)
            ->where('subject_id', $booking->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $guest = $booking->guest;
        $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'Walk-in Guest';
        $roomsStr = static::formatRooms($booking->booked_rooms);
        $checkOutTime = $booking->check_out_datetime ? Carbon::parse($booking->check_out_datetime) : null;
        $timeStr = $checkOutTime ? $checkOutTime->format('M d, Y h:i A') : 'N/A';

        return ActivityLog::create([
            'staff_id' => null, // Explicitly NULL for system-generated
            'activity_type' => 'overdue',
            'action' => 'booking_overdue',
            'subject_type' => Booking::class,
            'subject_id' => $booking->id,
            'guest_name' => $guestName,
            'room_number' => $roomsStr,
            'booking_type' => $booking->stay_type ?? 'overnight',
            'amount' => (float) ($booking->invoice?->grand_total ?? 0),
            'payment_method' => $booking->payment_method,
            'reference_number' => 'BK-'.str_pad($booking->id, 4, '0', STR_PAD_LEFT),
            'description' => 'Booking #BK-'.str_pad($booking->id, 4, '0', STR_PAD_LEFT)." reached Overdue status (Scheduled check-out: {$timeStr})",
            'details' => [
                'booking_id' => $booking->id,
                'guest_name' => $guestName,
                'room_number' => $roomsStr,
                'scheduled_checkout' => $checkOutTime?->toIso8601String(),
                'detected_at' => now()->toIso8601String(),
                'origin' => 'system_generated',
            ],
            'created_at' => $checkOutTime && $checkOutTime->isPast() ? $checkOutTime : now(),
        ]);
    }

    /**
     * Scan and automatically record overdue bookings without duplicates.
     */
    public static function syncOverdueBookings(): int
    {
        $now = now()->setTimezone('Asia/Manila');

        $overdueBookings = Booking::with('guest', 'invoice')
            ->where('status', 'checked_in')
            ->where('check_out_datetime', '<', $now)
            ->where(function ($q) {
                $q->whereDoesntHave('invoice')
                    ->orWhereHas('invoice', fn ($i) => $i->where('status', '!=', 'paid'));
            })
            ->get();

        $count = 0;
        foreach ($overdueBookings as $booking) {
            $created = static::logOverdue($booking);
            if ($created && $created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 4. Record Receptionist Login exclusively.
     */
    public static function logLogin(Staff $staff, ?Request $request = null): ActivityLog
    {
        $now = now()->setTimezone('Asia/Manila');

        return ActivityLog::create([
            'staff_id' => $staff->id,
            'activity_type' => 'login',
            'action' => 'receptionist_login',
            'subject_type' => Staff::class,
            'subject_id' => $staff->id,
            'guest_name' => null,
            'room_number' => null,
            'booking_type' => null,
            'amount' => null,
            'payment_method' => null,
            'reference_number' => null,
            'description' => "Receptionist {$staff->first_name} {$staff->last_name} logged in (@{$staff->username})",
            'details' => [
                'username' => $staff->username,
                'staff_name' => trim($staff->first_name.' '.$staff->last_name),
                'email' => $staff->email,
                'login_date' => $now->toDateString(),
                'login_time' => $now->format('h:i:s A'),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ],
            'created_at' => $now,
        ]);
    }

    /**
     * 5. Record newly created booking (Immediate room occupancy).
     */
    public static function logBooking(Booking $booking, ?int $staffId = null, string $type = 'immediate'): ActivityLog
    {
        $staffId = $staffId ?? static::currentStaffId();
        $guest = $booking->guest;
        $guestName = $guest ? trim(($guest->fname ?? '').' '.($guest->lname ?? '')) : 'Walk-in Guest';
        $roomsStr = static::formatRooms($booking->booked_rooms);
        $stayType = $booking->stay_type ?? 'overnight';

        $checkIn = $booking->check_in_datetime ? Carbon::parse($booking->check_in_datetime)->format('M d, Y h:i A') : 'N/A';
        $checkOut = $booking->check_out_datetime ? Carbon::parse($booking->check_out_datetime)->format('M d, Y h:i A') : 'N/A';

        $descType = ($type === 'reservation' || $booking->status === 'reserved') ? 'Reservation' : 'Immediate Booking';

        return ActivityLog::create([
            'staff_id' => $staffId,
            'activity_type' => 'booking',
            'action' => 'booking_created',
            'subject_type' => Booking::class,
            'subject_id' => $booking->id,
            'guest_name' => $guestName,
            'room_number' => $roomsStr,
            'booking_type' => $stayType,
            'amount' => (float) ($booking->deposit ?? $booking->invoice?->grand_total ?? 0),
            'payment_method' => $booking->payment_method ?? 'cash',
            'reference_number' => 'BK-'.str_pad($booking->id, 4, '0', STR_PAD_LEFT),
            'description' => "Created {$descType} for {$guestName} in {$roomsStr} ({$stayType})",
            'details' => [
                'booking_id' => $booking->id,
                'code' => 'BK-'.str_pad($booking->id, 4, '0', STR_PAD_LEFT),
                'booking_mode' => $type,
                'stay_type' => $stayType,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'rooms' => $roomsStr,
                'with_breakfast' => (bool) $booking->with_breakfast,
                'short_hours' => $booking->short_stay_hours,
                'discount_type' => $booking->discount_type,
            ],
            'created_at' => $booking->created_at ?? now(),
        ]);
    }

    /**
     * 5. Record newly created reservation (Future reservation).
     */
    public static function logReservation(Reservation $reservation, ?int $staffId = null): ActivityLog
    {
        $staffId = $staffId ?? static::currentStaffId();
        $guestName = $reservation->guest_name ?? 'Guest';
        $roomsStr = static::formatRooms($reservation->selected_rooms);
        $stayType = $reservation->stay_type ?? 'overnight';

        $start = $reservation->reservation_start_date ? Carbon::parse($reservation->reservation_start_date)->format('M d, Y h:i A') : 'N/A';
        $end = $reservation->reservation_end_date ? Carbon::parse($reservation->reservation_end_date)->format('M d, Y h:i A') : 'N/A';
        $deposit = (float) ($reservation->deposit ?? 0);

        return ActivityLog::create([
            'staff_id' => $staffId,
            'activity_type' => 'booking',
            'action' => 'reservation_created',
            'subject_type' => Reservation::class,
            'subject_id' => $reservation->id,
            'guest_name' => $guestName,
            'room_number' => $roomsStr,
            'booking_type' => 'reservation',
            'amount' => $deposit,
            'payment_method' => $reservation->payment_method ?? 'cash',
            'reference_number' => $reservation->reference_number ?? ('RES-'.str_pad($reservation->id, 4, '0', STR_PAD_LEFT)),
            'description' => "Created Future Reservation for {$guestName} in {$roomsStr} (Arrival: {$start})",
            'details' => [
                'reservation_id' => $reservation->id,
                'booking_mode' => 'reservation',
                'stay_type' => $stayType,
                'arrival' => $start,
                'departure' => $end,
                'rooms' => $roomsStr,
                'deposit' => $deposit,
                'guests' => $reservation->guests ?? 1,
            ],
            'created_at' => $reservation->created_at ?? now(),
        ]);
    }
}
