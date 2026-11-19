<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingOverdueFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createBookingWithInvoice(
        string $fname,
        string $status,
        Carbon $checkIn,
        Carbon $checkOut,
        string $invoiceStatus = 'unpaid'
    ): Booking {
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => 'Test',
            'phone' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => $checkIn,
            'check_out_datetime' => $checkOut,
            'booked_rooms' => [1],
            'status' => $status,
            'stay_type' => 'short_stay',
            'short_stay_hours' => 3,
        ]);

        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-'.uniqid(),
            'room_charges' => 600.00,
            'grand_total' => 600.00,
            'status' => $invoiceStatus,
            'due_date' => $checkOut->toDateString(),
        ]);

        return $booking;
    }

    public function test_overdue_tab_is_positioned_between_in_house_and_completed(): void
    {
        $response = $this->get(route('booking.index'));
        $response->assertOk();

        $content = $response->getContent();

        $inHousePos = strpos($content, 'filter=in_house');
        $overduePos = strpos($content, 'filter=overdue');
        $completedPos = strpos($content, 'filter=completed');

        $this->assertNotFalse($inHousePos, 'In house filter tab should exist.');
        $this->assertNotFalse($overduePos, 'Overdue filter tab should exist.');
        $this->assertNotFalse($completedPos, 'Completed filter tab should exist.');

        $this->assertTrue(
            $inHousePos < $overduePos && $overduePos < $completedPos,
            'Overdue filter tab must be positioned between In house and Completed.'
        );

        $response->assertSee('Overdue');
    }

    public function test_selecting_overdue_filter_displays_only_bookings_with_overdue_status(): void
    {
        $now = now()->setTimezone('Asia/Manila');

        // 1. Overdue booking: checked in, checkout in past, unpaid invoice
        $overdueBooking = $this->createBookingWithInvoice(
            'OverdueGuest',
            'checked_in',
            $now->copy()->subHours(5),
            $now->copy()->subHours(2),
            'unpaid'
        );

        // 2. Active In House booking: checked in, checkout in future, unpaid invoice
        $inHouseBooking = $this->createBookingWithInvoice(
            'ActiveGuest',
            'checked_in',
            $now->copy()->subHour(),
            $now->copy()->addHours(3),
            'unpaid'
        );

        // 3. Completed booking: checked out and paid
        $completedBooking = $this->createBookingWithInvoice(
            'CompletedGuest',
            'checked_out',
            $now->copy()->subHours(6),
            $now->copy()->subHours(3),
            'paid'
        );

        // 4. Cancelled booking
        $cancelledBooking = $this->createBookingWithInvoice(
            'CancelledGuest',
            'cancelled',
            $now->copy()->subHours(4),
            $now->copy()->subHours(1),
            'unpaid'
        );

        // Request with filter=overdue
        $response = $this->get(route('booking.index', ['filter' => 'overdue']));
        $response->assertOk();

        $bookings = $response->viewData('bookings');
        $this->assertCount(1, $bookings);
        $this->assertSame($overdueBooking->id, $bookings->first()->id);

        // Verify HTML displays OverdueGuest and the Overdue status badge
        $response->assertSee('OverdueGuest');
        $response->assertDontSee('ActiveGuest');
        $response->assertDontSee('CompletedGuest');
        $response->assertDontSee('CancelledGuest');

        // Status badge should show Overdue
        $response->assertSee('<span class="badge border border-danger text-danger bg-white">Overdue</span>', false);
    }
}
