<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    private function createGuestAndReservation(string $fname, string $lname, int $index): Reservation
    {
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => '09123456789',
        ]);

        return Reservation::create([
            'guest_id' => $guest->id,
            'guest_name' => "{$fname} {$lname}",
            'phone_number' => '09123456789',
            'reservation_start_date' => Carbon::now()->addDay(),
            'reservation_end_date' => Carbon::now()->addDays(2),
            'selected_rooms' => [1],
            'number_of_rooms' => 1,
            'stay_type' => 'overnight',
            'status' => 'reserved',
            'deposit' => 500.00,
            'payment_method' => 'cash',
            'created_at' => Carbon::now()->subMinutes(20 - $index),
        ]);
    }

    public function test_reservations_are_paginated_to_maximum_of_8_per_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createGuestAndReservation("ResGuest{$i}", 'Test', $i);
        }

        $response = $this->get(route('reservation.index'));

        $response->assertOk();
        $reservations = $response->viewData('reservations');

        $this->assertSame(8, $reservations->perPage());
        $this->assertSame(8, $reservations->count());
        $this->assertSame(12, $reservations->total());
        $this->assertSame(2, $reservations->lastPage());
        $this->assertSame(1, $reservations->currentPage());

        // Verify pagination control link to page 2 is present in HTML
        $response->assertSee('page=2');
        $response->assertSee('Showing 1 to 8 of 12 reservation(s)');
        $response->assertSee('Previous');
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }

    public function test_navigating_to_second_page_of_reservations(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createGuestAndReservation("ResGuest{$i}", 'Test', $i);
        }

        $response = $this->get(route('reservation.index', ['page' => 2]));

        $response->assertOk();
        $reservations = $response->viewData('reservations');

        $this->assertSame(8, $reservations->perPage());
        $this->assertSame(4, $reservations->count());
        $this->assertSame(12, $reservations->total());
        $this->assertSame(2, $reservations->currentPage());

        $response->assertSee('Showing 9 to 12 of 12 reservation(s)');
    }

    public function test_reservation_pagination_preserves_filter_query_parameter(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $res = $this->createGuestAndReservation("CancelledGuest{$i}", 'Test', $i);
            $res->update(['status' => 'cancelled']);
        }

        $response = $this->get(route('reservation.index', ['filter' => 'cancelled']));

        $response->assertOk();
        $response->assertSee('filter=cancelled');
        $response->assertSee('page=2');
    }

    public function test_pagination_controls_display_numbered_buttons_and_adjust_to_page_count(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->createGuestAndReservation("ResGuest{$i}", 'Dynamic', $i);
        }

        $response = $this->get(route('reservation.index'));

        $response->assertOk();
        $reservations = $response->viewData('reservations');
        $this->assertSame(8, $reservations->perPage());
        $this->assertSame(3, $reservations->lastPage());
        $this->assertSame(20, $reservations->total());

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
