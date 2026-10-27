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

        \Illuminate\Support\Facades\View::composer('partials.topbar', function ($view) {
            // Find unpaid invoices for guests whose checkout is due today or past due
            $dueInvoices = \App\Models\Invoice::with('booking.guest')
                ->where('status', 'unpaid')
                ->whereHas('booking', function ($query) {
                    $query->whereDate('check_out_datetime', '<=', now()->setTimezone('Asia/Manila')->toDateString());
                })->get();
            
            // Map to dueGuests format expected by topbar
            $dueGuests = $dueInvoices->map(function ($invoice) {
                return (object) [
                    'name' => ($invoice->booking->guest->fname ?? 'Unknown') . ' ' . ($invoice->booking->guest->lname ?? ''),
                    'room_number' => is_array($invoice->booking->booked_rooms) ? implode(', ', $invoice->booking->booked_rooms) : $invoice->booking->booked_rooms,
                    'amount' => $invoice->grand_total,
                    'checkout_time' => \Carbon\Carbon::parse($invoice->booking->check_out_datetime)->format('M d, Y h:i A')
                ];
            });
            // Rooms with a stay happening right now, for the Add Amenities modal (works on every page)
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

            $view->with('dueGuests', $dueGuests)->with('occupiedRoomsList', $occupiedRoomsList);
        });                                    
    }                                           
}                                               