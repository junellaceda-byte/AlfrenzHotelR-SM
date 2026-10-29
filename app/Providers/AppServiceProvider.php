<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        \Illuminate\Support\Facades\View::composer(['partials.topbar', 'content.receptionist'], function ($view) {
            $now = now()->setTimezone('Asia/Manila');

            $dueInvoices = \App\Models\Invoice::with('booking.guest')
                ->where('status', 'unpaid')
                ->whereHas('booking', function ($query) use ($now) {
                    $query->where('status', 'checked_in')
                          ->where('check_out_datetime', '<=', $now->copy()->addMinutes(30));
                })->get()
                ->sortBy(fn ($invoice) => $invoice->booking->check_out_datetime);

            $dueGuests = $dueInvoices->map(function ($invoice) use ($now) {
                $checkOut = \Carbon\Carbon::parse($invoice->booking->check_out_datetime);

                return (object) [
                    'name' => trim(($invoice->booking->guest->fname ?? 'Guest') . ' ' . ($invoice->booking->guest->lname ?? '')),
                    'room_number' => collect($invoice->booking->booked_rooms)
                        ->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))->implode(', '),
                    'checkout_time' => $checkOut->format('h:i A'),
                    'is_overdue' => $checkOut->lte($now),
                    'minutes' => max(0, (int) floor($now->diffInMinutes($checkOut, false))),
                ];
            })->values();

            $view->with('dueGuests', $dueGuests);
        });

        // The Add Amenities modal lists the rooms with a stay happening right now, on every page
        \Illuminate\Support\Facades\View::composer('partials.topbar', function ($view) {
            $now = now()->setTimezone('Asia/Manila');

            $occupiedRoomsList = \App\Models\Room::all()->filter(function ($room) use ($now) {
                return \App\Models\Booking::where(function ($q) use ($room) {
                        $q->whereJsonContains('booked_rooms', (string) $room->id)
                          ->orWhereJsonContains('booked_rooms', $room->id);
                    })
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->where('check_in_datetime', '<=', $now)
                    ->where('check_out_datetime', '>=', $now)
                    ->exists();
            })->values();

            $view->with('occupiedRoomsList', $occupiedRoomsList);
        });
    }
}