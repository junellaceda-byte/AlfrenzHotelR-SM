<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    private function createGuestAndBooking(string $fname, string $lname): Booking
    {
        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => '09123456789',
        ]);

        return Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addHours(6),
            'booked_rooms' => [1],
        ]);
    }

    public function test_pending_payments_are_paginated_to_maximum_of_9_per_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $booking = $this->createGuestAndBooking("PendingGuest{$i}", 'Test');
            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-P-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'unpaid',
                'created_at' => Carbon::now()->subMinutes(12 - $i),
            ]);
        }

        $response = $this->get(route('content.billing'));

        $response->assertOk();
        $pending = $response->viewData('pendingPayments');
        $this->assertSame(9, $pending->perPage());
        $this->assertSame(9, $pending->count());
        $this->assertSame(12, $pending->total());
        $this->assertSame(2, $pending->lastPage());
        $this->assertSame(1, $pending->currentPage());

        // Verify pagination control link to page 2 is present in HTML
        $response->assertSee('pending_page=2');
        $response->assertSee('Showing 1 to 9 of 12 result(s)');
        $response->assertSee('Previous');
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }

    public function test_confirmed_payments_are_paginated_to_maximum_of_9_per_page(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $booking = $this->createGuestAndBooking("PaidGuest{$i}", 'Test');
            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-C-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'paid',
                'created_at' => Carbon::now()->subMinutes(15 - $i),
            ]);

            PaymentLog::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => 600.00,
                'payment_method' => 'cash',
                'status' => 'successful',
            ]);
        }

        $response = $this->get(route('content.billing'));

        $response->assertOk();
        $confirmed = $response->viewData('confirmedPayments');
        $this->assertSame(9, $confirmed->perPage());
        $this->assertSame(9, $confirmed->count());
        $this->assertSame(15, $confirmed->total());
        $this->assertSame(2, $confirmed->lastPage());
        $this->assertSame(1, $confirmed->currentPage());

        // Verify pagination control link to page 2 is present in HTML
        $response->assertSee('confirmed_page=2');
        $response->assertSee('Showing 1 to 9 of 15 result(s)');
        $response->assertSee('Previous');
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }

    public function test_navigating_pages_for_pending_and_confirmed_payments(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $booking = $this->createGuestAndBooking("PendingGuest{$i}", 'Test');
            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-P-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'unpaid',
                'created_at' => Carbon::now()->subMinutes(12 - $i),
            ]);
        }

        for ($i = 1; $i <= 15; $i++) {
            $booking = $this->createGuestAndBooking("PaidGuest{$i}", 'Test');
            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-C-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'paid',
                'created_at' => Carbon::now()->subMinutes(15 - $i),
            ]);

            PaymentLog::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => 600.00,
                'payment_method' => 'cash',
                'status' => 'successful',
            ]);
        }

        // Navigate pending to page 2
        $responsePendingPage2 = $this->get(route('content.billing', ['pending_page' => 2]));
        $responsePendingPage2->assertOk();
        $pendingP2 = $responsePendingPage2->viewData('pendingPayments');
        $this->assertSame(3, $pendingP2->count());
        $this->assertSame(2, $pendingP2->currentPage());
        $responsePendingPage2->assertSee('Showing 10 to 12 of 12 result(s)');

        // Navigate confirmed to page 2
        $responseConfirmedPage2 = $this->get(route('content.billing', ['confirmed_page' => 2]));
        $responseConfirmedPage2->assertOk();
        $confirmedP2 = $responseConfirmedPage2->viewData('confirmedPayments');
        $this->assertSame(6, $confirmedP2->count());
        $this->assertSame(2, $confirmedP2->currentPage());
        $responseConfirmedPage2->assertSee('Showing 10 to 15 of 15 result(s)');
    }

    public function test_newest_first_ordering_is_maintained_with_pagination(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $booking = $this->createGuestAndBooking("Guest{$i}", 'OrderTest');
            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-ORD-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 500.00,
                'grand_total' => 500.00,
                'status' => 'unpaid',
            ]);
            $invoice->created_at = Carbon::now()->subMinutes(20 - $i);
            $invoice->save();
        }

        $responsePage1 = $this->get(route('content.billing', ['pending_page' => 1]));
        $pendingPage1 = $responsePage1->viewData('pendingPayments');

        // Newest invoice (i = 11) should be first
        $this->assertSame('INV-ORD-0011', $pendingPage1->first()->invoice_number);

        $responsePage2 = $this->get(route('content.billing', ['pending_page' => 2]));
        $pendingPage2 = $responsePage2->viewData('pendingPayments');

        // Oldest invoices should be on page 2
        $this->assertSame('INV-ORD-0001', $pendingPage2->last()->invoice_number);
    }

    public function test_billing_pagination_controls_display_numbered_buttons_and_adjust_to_page_count(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $booking = $this->createGuestAndBooking("DynGuest{$i}", 'Test');
            Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-DYN-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'unpaid',
                'created_at' => Carbon::now()->subMinutes(25 - $i),
            ]);
        }

        $response = $this->get(route('content.billing'));

        $response->assertOk();
        $pending = $response->viewData('pendingPayments');
        $this->assertSame(9, $pending->perPage());
        $this->assertSame(3, $pending->lastPage());
        $this->assertSame(20, $pending->total());

        // Previous button is disabled on page 1
        $response->assertSee('<li class="page-item disabled" aria-disabled="true">', false);
        $response->assertSee('Previous');
        // Page 1 is active
        $response->assertSee('<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>', false);
        // Pages 2 and 3 are present
        $response->assertSee('pending_page=2');
        $response->assertSee('pending_page=3');
        // Next button is present
        $response->assertSee('Next');
        $response->assertDontSee('&lsaquo;');
        $response->assertDontSee('&rsaquo;');
    }

    public function test_confirmed_payments_pagination_does_not_trigger_or_open_billing_process_modal(): void
    {
        $unpaidBooking = $this->createGuestAndBooking('UnpaidGuest', 'Test');
        $unpaidInvoice = Invoice::create([
            'booking_id' => $unpaidBooking->id,
            'invoice_number' => 'INV-UNPAID-01',
            'room_charges' => 500.00,
            'grand_total' => 500.00,
            'status' => 'unpaid',
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $booking = $this->createGuestAndBooking("PaidGuest{$i}", 'Test');
            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-C-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'room_charges' => 600.00,
                'grand_total' => 600.00,
                'status' => 'paid',
                'created_at' => Carbon::now()->subMinutes(15 - $i),
            ]);

            PaymentLog::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => 600.00,
                'payment_method' => 'cash',
                'status' => 'successful',
            ]);
        }

        // When viewing billing with an edit_id (e.g. from clicking Pay Now)
        $responseWithEditId = $this->get(route('content.billing', ['edit_id' => $unpaidInvoice->id]));
        $responseWithEditId->assertOk();
        $this->assertNotNull($responseWithEditId->viewData('selectedInvoice'));
        $responseWithEditId->assertSee('billingModal.show();');

        // Confirmed payments pagination links must NOT carry edit_id
        $confirmed = $responseWithEditId->viewData('confirmedPayments');
        $this->assertSame(2, $confirmed->lastPage());
        $responseWithEditId->assertDontSee('confirmed_page=2&amp;edit_id=');
        $responseWithEditId->assertDontSee('confirmed_page=2&edit_id=');
        $responseWithEditId->assertDontSee('edit_id='.$unpaidInvoice->id.'&amp;confirmed_page=2');
        $responseWithEditId->assertDontSee('edit_id='.$unpaidInvoice->id.'&confirmed_page=2');

        // Navigating to page 2 of confirmed payments must NOT load selectedInvoice or open the billing modal
        $responsePage2 = $this->get(route('content.billing', ['confirmed_page' => 2]));
        $responsePage2->assertOk();
        $this->assertNull($responsePage2->viewData('selectedInvoice'));
        $responsePage2->assertDontSee('billingModal.show();');
        $this->assertSame(6, $responsePage2->viewData('confirmedPayments')->count());

        // Even if confirmed_page is requested with edit_id, the billing modal must NOT open
        $responsePage2WithEdit = $this->get(route('content.billing', [
            'confirmed_page' => 2,
            'edit_id' => $unpaidInvoice->id,
        ]));
        $responsePage2WithEdit->assertOk();
        $this->assertNull($responsePage2WithEdit->viewData('selectedInvoice'));
        $responsePage2WithEdit->assertDontSee('billingModal.show();');
    }
}
