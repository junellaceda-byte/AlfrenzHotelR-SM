<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createGuestAndBooking(string $fname, string $lname, int $index): Booking
    {
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addHours(6),
            'booked_rooms' => [1],
            'status' => 'checked_in',
            'created_at' => Carbon::now()->subMinutes(20 - $index),
        ]);

        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-BK-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            'room_charges' => 600.00,
            'grand_total' => 600.00,
            'status' => 'unpaid',
            'created_at' => Carbon::now()->subMinutes(20 - $index),
        ]);

        return $booking;
    }

    public function test_bookings_are_paginated_to_maximum_of_8_per_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createGuestAndBooking("BookingGuest{$i}", 'Test', $i);
        }

        $response = $this->get(route('booking.index'));

        $response->assertOk();
        $bookings = $response->viewData('bookings');

        $this->assertSame(8, $bookings->perPage());
        $this->assertSame(8, $bookings->count());
        $this->assertSame(12, $bookings->total());
        $this->assertSame(2, $bookings->lastPage());
        $this->assertSame(1, $bookings->currentPage());

        // Verify pagination control link to page 2 is present in HTML
        $response->assertSee('page=2');
        $response->assertSee('Showing 1 to 8 of 12 booking(s)');
        $response->assertSee('Previous');
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }

    public function test_navigating_to_second_page_of_bookings(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createGuestAndBooking("BookingGuest{$i}", 'Test', $i);
        }

        $response = $this->get(route('booking.index', ['page' => 2]));

        $response->assertOk();
        $bookings = $response->viewData('bookings');

        $this->assertSame(8, $bookings->perPage());
        $this->assertSame(4, $bookings->count());
        $this->assertSame(12, $bookings->total());
        $this->assertSame(2, $bookings->currentPage());

        $response->assertSee('Showing 9 to 12 of 12 booking(s)');
    }

    public function test_booking_pagination_preserves_filter_query_parameter(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->createGuestAndBooking("FilteredGuest{$i}", 'Test', $i);
        }

        $response = $this->get(route('booking.index', ['filter' => 'in_house']));

        $response->assertOk();
        $response->assertSee('filter=in_house');
        $response->assertSee('page=2');
    }

    public function test_pagination_controls_display_numbered_buttons_and_adjust_to_page_count(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->createGuestAndBooking("BookingGuest{$i}", 'Dynamic', $i);
        }

        $response = $this->get(route('booking.index'));

        $response->assertOk();
        $bookings = $response->viewData('bookings');
        $this->assertSame(8, $bookings->perPage());
        $this->assertSame(3, $bookings->lastPage());
        $this->assertSame(20, $bookings->total());

        // Previous button is disabled on page 1
        $response->assertSee('<li class="page-item disabled" aria-disabled="true">', false);
        $response->assertSee('Previous');
        // Page 1 is active
        $response->assertSee('<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>', false);
        // Pages 2 and 3 are present
        $response->assertSee('page=2');
        $response->assertSee('page=3');
        // Next button is present
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }
}
