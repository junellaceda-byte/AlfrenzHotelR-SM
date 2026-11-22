<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedTableLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();
    }

    public function test_reservation_table_has_fixed_layout_and_column_widths(): void
    {
        Reservation::create([
            'guest_name' => 'VeryLongFirstNameWithManyCharactersAndNoSpacesToTestTruncation ReallyLongLastName',
            'phone_number' => '09123456789123456789',
            'reservation_start_date' => Carbon::now()->addDay(),
            'reservation_end_date' => Carbon::now()->addDays(2),
            'stay_type' => 'overnight',
            'with_breakfast' => true,
            'selected_rooms' => [1, 2, 3],
            'number_of_rooms' => 3,
            'deposit' => 1500.00,
            'payment_method' => 'ewallet',
            'ewallet_type' => 'GCash',
            'reference_number' => 'REF98765432109876543210',
            'status' => 'reserved',
        ]);

        $response = $this->get(route('reservation.index'));
        $response->assertOk();

        // Check for fixed table layout, colgroup, and wrapping without truncation
        $response->assertSee('table-layout: fixed', false);
        $response->assertSee('reservation-table', false);
        $response->assertSee('<colgroup>', false);
        $response->assertSee('style="width: 20%;"', false);
        $response->assertSee('style="width: 17%;"', false);
        $response->assertSee('word-break: break-word', false);
        $response->assertDontSee('text-truncate', false);
        $response->assertSee('VeryLongFirstNameWithManyCharactersAndNoSpacesToTestTruncation ReallyLongLastName');
    }

    public function test_booking_history_table_has_fixed_layout_and_column_widths(): void
    {
        $guest = Guest::create([
            'fname' => 'SuperLongFirstNameWithoutAnySpacesForTestingPurposes',
            'lname' => 'SuperLongLastNameWithoutAnySpacesForTestingPurposes',
            'phone' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addHours(6),
            'booked_rooms' => [1, 2],
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
            'status' => 'checked_in',
        ]);

        Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-BK-0001',
            'room_charges' => 800.00,
            'grand_total' => 800.00,
            'status' => 'unpaid',
        ]);

        $response = $this->get(route('booking.index'));
        $response->assertOk();

        $response->assertSee('table-layout: fixed', false);
        $response->assertSee('booking-history-table', false);
        $response->assertSee('<colgroup>', false);
        $response->assertSee('style="width: 9%;"', false);
        $response->assertSee('style="width: 14%;"', false);
        $response->assertSee('word-break: break-word', false);
        $response->assertDontSee('text-truncate', false);
        $response->assertSee('SuperLongFirstNameWithoutAnySpacesForTestingPurposes');

        // Phone, Stay Type, Discount, Paid On are moved to View Details modal and removed from the table headers
        $response->assertDontSee('<th class="py-3 px-3">Phone</th>', false);
        $response->assertDontSee('<th class="py-3 px-3">Stay Type</th>', false);
        $response->assertDontSee('<th class="py-3 px-3">Discount</th>', false);
        $response->assertDontSee('<th class="py-3 px-3">Paid On</th>', false);

        // Fields must remain present in View Details popup modal
        $response->assertSee('data-field="phone"', false);
        $response->assertSee('data-field="stay"', false);
        $response->assertSee('data-field="discount"', false);
        $response->assertSee('data-field="paid_on"', false);
    }

    public function test_billing_tables_have_fixed_layout_and_column_widths(): void
    {
        $guest1 = Guest::create([
            'fname' => 'PendingGuestWithVeryLongNameToVerifyConsistentFixedTableLayout',
            'lname' => 'LastName',
            'phone' => '09123456789',
        ]);

        $booking1 = Booking::create([
            'guest_id' => $guest1->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addHours(6),
            'booked_rooms' => [1],
            'status' => 'checked_in',
        ]);

        Invoice::create([
            'booking_id' => $booking1->id,
            'invoice_number' => 'INV-P-0001',
            'room_charges' => 500.00,
            'grand_total' => 500.00,
            'status' => 'unpaid',
        ]);

        $guest2 = Guest::create([
            'fname' => 'ConfirmedGuestWithVeryLongNameToVerifyConsistentFixedTableLayout',
            'lname' => 'LastName',
            'phone' => '09123456789',
        ]);

        $booking2 = Booking::create([
            'guest_id' => $guest2->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addHours(6),
            'booked_rooms' => [2],
            'status' => 'checked_out',
        ]);

        $invoice2 = Invoice::create([
            'booking_id' => $booking2->id,
            'invoice_number' => 'INV-C-0001',
            'room_charges' => 500.00,
            'grand_total' => 500.00,
            'status' => 'paid',
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice2->id,
            'amount_paid' => 500.00,
            'payment_method' => 'cash',
            'status' => 'successful',
        ]);

        $response = $this->get(route('content.billing'));
        $response->assertOk();

        $response->assertSee('billing-pending-table', false);
        $response->assertSee('billing-confirmed-table', false);
        $response->assertSee('table-layout: fixed', false);
        $response->assertSee('style="width: 20%;"', false);
        $response->assertSee('word-break: break-word', false);
        $response->assertDontSee('text-truncate', false);
        $response->assertSee('PendingGuestWithVeryLongNameToVerifyConsistentFixedTableLayout');
        $response->assertSee('ConfirmedGuestWithVeryLongNameToVerifyConsistentFixedTableLayout');

        // Method column is removed from Confirmed Payments table header, but remains in the View Details modal
        $response->assertDontSee('<th>Method</th>', false);
        $response->assertSee('id="pd_method"', false);

        // Pay Now button is styled to stay on a single horizontal line without wrapping
        $response->assertSee('class="btn btn-sm btn-outline-primary text-nowrap px-2 btn-pay-now"', false);
        $response->assertSee('Pay Now', false);
    }

    public function test_booking_history_view_details_modal_amenities_status_logic(): void
    {
        $guest = Guest::create([
            'fname' => 'AmenityStatus',
            'lname' => 'Guest',
            'phone' => '09123456780',
        ]);

        Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456780',
            'check_in_datetime' => Carbon::now()->subDays(2),
            'check_out_datetime' => Carbon::now()->subDay(),
            'booked_rooms' => [1],
            'status' => 'checked_out',
        ]);

        $response = $this->get(route('booking.index'));
        $response->assertOk();

        $response->assertSee("var emptyAmenityText = (d.status && d.status.toLowerCase() === 'completed') ? 'None' : 'No amenities added yet';", false);
    }

    public function test_billing_total_amount_column_adjusts_width_and_prevents_currency_cutoff_for_large_amounts(): void
    {
        $guest1 = Guest::create([
            'fname' => 'VIP',
            'lname' => 'CorporateGuest',
            'phone' => '09123456789',
        ]);

        $booking1 = Booking::create([
            'guest_id' => $guest1->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now(),
            'check_out_datetime' => Carbon::now()->addDays(14),
            'booked_rooms' => [101, 102, 103],
            'status' => 'checked_in',
        ]);

        Invoice::create([
            'booking_id' => $booking1->id,
            'invoice_number' => 'INV-LARGE-P01',
            'room_charges' => 1500000.00,
            'grand_total' => 1500000.00,
            'status' => 'unpaid',
        ]);

        $guest2 = Guest::create([
            'fname' => 'Executive',
            'lname' => 'GroupClient',
            'phone' => '09987654321',
        ]);

        $booking2 = Booking::create([
            'guest_id' => $guest2->id,
            'phone' => '09987654321',
            'check_in_datetime' => Carbon::now()->subDays(10),
            'check_out_datetime' => Carbon::now()->subDay(),
            'booked_rooms' => [201, 202],
            'status' => 'checked_out',
        ]);

        $invoice2 = Invoice::create([
            'booking_id' => $booking2->id,
            'invoice_number' => 'INV-LARGE-C01',
            'room_charges' => 2750000.50,
            'grand_total' => 2750000.50,
            'status' => 'paid',
        ]);

        PaymentLog::create([
            'invoice_id' => $invoice2->id,
            'amount_paid' => 2750000.50,
            'payment_method' => 'bank_transfer',
            'status' => 'successful',
        ]);

        $response = $this->get(route('content.billing'));
        $response->assertOk();

        // 1. Full currency values are rendered with text-nowrap and col-total-amount class to prevent breaking
        $response->assertSee('class="col-total-amount text-nowrap">₱ 1,500,000.00</td>', false);
        $response->assertSee('class="col-total-amount text-nowrap">₱ 2,750,000.50</td>', false);

        // 2. Both tables define col-total-amount column and automatically adjust width to accommodate the large amount
        $response->assertSee('col-total-amount', false);
        $response->assertSee('adjustBillingTotalAmountWidth', false);

        // 3. Formatted string has 16 characters ("₱ 2,750,000.50"), width expands well beyond default 110px/115px
        $response->assertSee('class="col-total-amount" style="width: 163px; min-width: 163px;"', false);
        $response->assertSee('min-width: 603px', false);

        // 4. Headers have col-total-amount and text-nowrap
        $response->assertSee('<th class="col-total-amount text-nowrap">Total Amount</th>', false);

        // 5. Responsive table wrapper and sticky headers are preserved
        $response->assertSee('table-responsive flex-grow-1', false);
        $response->assertSee('billing-pending-table', false);
        $response->assertSee('billing-confirmed-table', false);
    }
}
