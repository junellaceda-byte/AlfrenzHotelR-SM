<?php

namespace App\Providers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use App\Services\SystemFormatService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Tells Laravel to render pagination links using Bootstrap 5 styling.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Due alerts: unpaid, checked-in guests whose check-out is within 30 minutes or already past.
        // Used by the top bar bell and the Dashboard notification panel.
        View::composer(['Receptionist.partials.topbar', 'Receptionist.content.receptionist', 'partials.topbar', 'content.receptionist'], function ($view) {
            $now = now()->setTimezone('Asia/Manila');

            $dueInvoices = Invoice::with('booking.guest')
                ->where('status', 'unpaid')
                ->whereHas('booking', function ($query) use ($now) {
                    $query->where('status', 'checked_in')
                        ->where('check_out_datetime', '<=', $now->copy()->addMinutes(30));
                })->get()
                ->sortBy(fn ($invoice) => $invoice->booking->check_out_datetime);

            $dueGuests = $dueInvoices->map(function ($invoice) use ($now) {
                $checkOut = Carbon::parse($invoice->booking->check_out_datetime);

                return (object) [
                    'name' => trim(($invoice->booking->guest->fname ?? 'Guest').' '.($invoice->booking->guest->lname ?? '')),
                    'room_number' => collect($invoice->booking->booked_rooms)
                        ->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))->implode(', '),
                    'checkout_time' => SystemFormatService::formatTime($checkOut),
                    'is_overdue' => $checkOut->lte($now),
                    'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
                ];
            })->values();

            $view->with('dueGuests', $dueGuests);
        });

        // The Add Amenities modal lists the rooms with a stay happening right now, on every page
        View::composer(['Receptionist.partials.topbar', 'partials.topbar'], function ($view) {
            $now = now()->setTimezone('Asia/Manila');

            $occupiedRoomsList = Room::all()->filter(function ($room) use ($now) {
                $booking = Booking::with('guest')->where(function ($q) use ($room) {
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

                if ($booking) {
                    $room->guest_name = $booking->guest ? trim(($booking->guest->fname ?? 'Guest').' '.($booking->guest->lname ?? '')) : 'Guest';
                    $room->booking_id = $booking->id;

                    return true;
                }

                return false;
            })->values();

            $view->with('occupiedRoomsList', $occupiedRoomsList);
        });

        // Automatically share customizable amenities with Receptionist views
        View::composer([
            'Receptionist.partials.topbar',
            'Receptionist.modals.quickresmodal',
            'Receptionist.content.booking',
            'Receptionist.content.billing',
            'partials.topbar',
            'modals.quickresmodal',
        ], function ($view) {
            $view->with('availableAmenities', Amenity::where('offered', true)->get());
        });

        // System-wide timestamp formatting settings shared with all views
        View::composer('*', function ($view) {
            $view->with('dateFormat', SystemFormatService::dateFormat());
            $view->with('timeFormat', SystemFormatService::timeFormat());
            $view->with('is24Hour', SystemFormatService::is24Hour());
            $view->with('timeFormatType', SystemFormatService::timeFormatType());
        });
    }
}
