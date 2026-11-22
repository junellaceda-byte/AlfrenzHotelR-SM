<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistBookingSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_search_finds_record_from_any_pagination_page_and_adjusts_pagination(): void
    {
        // 8 records per page. Create 12 bookings.
        // The first 11 bookings are generic.
        for ($i = 1; $i <= 11; $i++) {
            $guest = Guest::create([
                'fname' => 'StandardGuest'.$i,
                'lname' => 'Doe',
                'phone' => '091700000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);

            Booking::create([
                'guest_id' => $guest->id,
                'phone' => $guest->phone,
                'booked_rooms' => [1],
                'nights' => 1,
                'stay_type' => 'overnight',
                'discount_type' => 'regular',
                'status' => 'checked_in',
                'check_in_datetime' => '2026-11-20 14:00:00',
                'check_out_datetime' => '2026-11-21 12:00:00',
                'created_at' => Carbon::parse('2026-11-20 10:00:00')->subHours($i),
            ]);
        }

        // The 12th booking is our specific target that would originally appear on page 2
        $targetGuest = Guest::create([
            'fname' => 'UniqueTarget',
            'lname' => 'Zimmerman',
            'phone' => '09998887766',
        ]);

        $targetBooking = Booking::create([
            'guest_id' => $targetGuest->id,
            'phone' => '09998887766',
            'booked_rooms' => [2],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-20 14:00:00',
            'check_out_datetime' => '2026-11-21 12:00:00',
            'created_at' => Carbon::parse('2026-11-19 08:00:00'),
        ]);

        // 1. Without search, page 1 has 8 items and does NOT display the target guest
        $defaultPageResponse = $this->get('/bookings');
        $defaultPageResponse->assertOk();
        $defaultPageResponse->assertSee('Showing 1 to 8 of 12 booking(s)');
        $defaultPageResponse->assertDontSee('UniqueTarget');

        // 2. Searching for "UniqueTarget" searches across all pages
        $searchResponse = $this->get('/bookings?search=UniqueTarget');
        $searchResponse->assertOk();
        $searchResponse->assertSee('UniqueTarget');
        $searchResponse->assertSee('Zimmerman');
        $searchResponse->assertSee('09998887766');
        $searchResponse->assertSee('BK-'.str_pad((string) $targetBooking->id, 4, '0', STR_PAD_LEFT));

        // 3. Pagination automatically adjusts to the filtered result (1 total)
        $searchResponse->assertSee('Showing 1 to 1 of 1 booking(s)');

        // 4. Searching by full name "UniqueTarget Zimmerman" also matches across all pages
        $fullNameSearchResponse = $this->get('/bookings?search=UniqueTarget+Zimmerman');
        $fullNameSearchResponse->assertOk();
        $fullNameSearchResponse->assertSee('UniqueTarget');
        $fullNameSearchResponse->assertSee('Showing 1 to 1 of 1 booking(s)');
    }

    public function test_search_by_booking_number_finds_record(): void
    {
        $guest = Guest::create([
            'fname' => 'Arthur',
            'lname' => 'Dent',
            'phone' => '09123456780',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456780',
            'booked_rooms' => [3],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-20 14:00:00',
            'check_out_datetime' => '2026-11-21 12:00:00',
        ]);

        $bookingCode = 'BK-'.str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);

        $response = $this->get('/bookings?search='.$bookingCode);
        $response->assertOk();
        $response->assertSee($bookingCode);
        $response->assertSee('Arthur');
        $response->assertSee('Dent');
    }

    public function test_search_results_exceeding_page_limit_paginate_correctly_with_query_string(): void
    {
        // Create 10 bookings with same last name 'Mercado'
        for ($i = 1; $i <= 10; $i++) {
            $guest = Guest::create([
                'fname' => 'Person'.$i,
                'lname' => 'Mercado',
                'phone' => '092200000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);

            Booking::create([
                'guest_id' => $guest->id,
                'phone' => $guest->phone,
                'booked_rooms' => [1],
                'nights' => 1,
                'stay_type' => 'overnight',
                'discount_type' => 'regular',
                'status' => 'checked_in',
                'check_in_datetime' => '2026-11-20 14:00:00',
                'check_out_datetime' => '2026-11-21 12:00:00',
            ]);
        }

        // Search for 'Mercado': 10 results, 8 per page -> 2 pages
        $page1Response = $this->get('/bookings?search=Mercado');
        $page1Response->assertOk();
        $page1Response->assertSee('Showing 1 to 8 of 10 booking(s)');
        // Ensure pagination link preserves search query parameter
        $page1Response->assertSee('search=Mercado');
        $page1Response->assertSee('page=2');

        // Page 2 shows the remaining 2 bookings
        $page2Response = $this->get('/bookings?search=Mercado&page=2');
        $page2Response->assertOk();
        $page2Response->assertSee('Showing 9 to 10 of 10 booking(s)');
    }

    public function test_search_combines_with_status_filter_correctly(): void
    {
        $guest1 = Guest::create([
            'fname' => 'Alice',
            'lname' => 'Wonderland',
            'phone' => '09111111111',
        ]);

        $b1 = Booking::create([
            'guest_id' => $guest1->id,
            'phone' => '09111111111',
            'booked_rooms' => [1],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-20 14:00:00',
            'check_out_datetime' => '2026-11-28 12:00:00',
        ]);

        Invoice::create([
            'booking_id' => $b1->id,
            'invoice_number' => 'INV-001',
            'room_charges' => 2000,
            'extra_charges_total' => 0,
            'grand_total' => 2000,
            'status' => 'unpaid',
        ]);

        $guest2 = Guest::create([
            'fname' => 'Bob',
            'lname' => 'Wonderland',
            'phone' => '09222222222',
        ]);

        Booking::create([
            'guest_id' => $guest2->id,
            'phone' => '09222222222',
            'booked_rooms' => [2],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'cancelled',
            'check_in_datetime' => '2026-11-20 14:00:00',
            'check_out_datetime' => '2026-11-21 12:00:00',
        ]);

        // Search 'Wonderland' with filter 'cancelled' -> only Bob
        $cancelledResponse = $this->get('/bookings?filter=cancelled&search=Wonderland');
        $cancelledResponse->assertOk();
        $cancelledResponse->assertSee('Bob');
        $cancelledResponse->assertDontSee('Alice');
        $cancelledResponse->assertSee('Showing 1 to 1 of 1 booking(s)');

        // Search 'Wonderland' with filter 'in_house' -> only Alice
        $inHouseResponse = $this->get('/bookings?filter=in_house&search=Wonderland');
        $inHouseResponse->assertOk();
        $inHouseResponse->assertSee('Alice');
        $inHouseResponse->assertDontSee('Bob');
        $inHouseResponse->assertSee('Showing 1 to 1 of 1 booking(s)');
    }

    public function test_searching_does_not_modify_booking_records_or_affect_billing(): void
    {
        $guest = Guest::create([
            'fname' => 'Invariant',
            'lname' => 'Checker',
            'phone' => '09333333333',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09333333333',
            'booked_rooms' => [5],
            'nights' => 1,
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
            'check_in_datetime' => '2026-11-20 14:00:00',
            'check_out_datetime' => '2026-11-21 12:00:00',
        ]);

        $this->get('/bookings?search=Invariant');

        // Booking record remains intact
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked_in',
            'phone' => '09333333333',
        ]);

        // Billing section remains functional and unaffected
        $billingResponse = $this->get('/billing');
        $billingResponse->assertOk();
    }
}
