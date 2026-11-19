<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\PaymentLog;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function createGuestBookingAndInvoice(string $fname = 'Maria', string $lname = 'Santos', ?Carbon $createdAt = null): Invoice
    {
        static $counter = 1;
        $roomNum = str_pad((string) ($counter++), 2, '0', STR_PAD_LEFT);
        $room = Room::firstOrCreate(
            ['room_number' => $roomNum],
            ['short_stay_rate' => 600.00, 'overnight_rate' => 2200.00, 'status' => 'occupied']
        );

        $guest = Guest::create([
            'fname' => $fname,
            'lname' => $lname,
            'phone' => '09123456789',
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => Carbon::now()->subHours(4),
            'check_out_datetime' => Carbon::now()->addHours(2),
            'booked_rooms' => [$room->id],
            'status' => 'checked_in',
        ]);

        $created = $createdAt ?? Carbon::now();

        return Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'room_charges' => 600.00,
            'extra_charges_total' => 0.00,
            'grand_total' => 600.00,
            'status' => 'unpaid',
            'created_at' => $created,
            'updated_at' => $created,
        ]);
    }

    public function test_processing_payment_with_ewallet_moves_guest_from_pending_to_confirmed_and_displays_ewallet(): void
    {
        $invoice = $this->createGuestBookingAndInvoice('Juan', 'Dela Cruz');

        // Verify initial state: present in Pending Payments, absent from Confirmed Payments
        $initialResponse = $this->get(route('content.billing'));
        $initialResponse->assertOk();
        $this->assertTrue($initialResponse->viewData('pendingPayments')->contains('id', $invoice->id));
        $this->assertFalse($initialResponse->viewData('confirmedPayments')->contains('id', $invoice->id));

        // Process payment with E-Wallet
        $response = $this->post(route('billing.processPayment', $invoice->id), [
            'payment_method' => 'ewallet',
        ]);

        $response->assertRedirect(route('content.billing'));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'invoice_id' => $invoice->id,
            'payment_method' => 'ewallet',
            'status' => 'successful',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $invoice->booking_id,
            'status' => 'checked_out',
            'payment_method' => 'ewallet',
        ]);

        // After payment: moved from Pending to Confirmed
        $pageResponse = $this->get(route('content.billing'));
        $pageResponse->assertOk();

        $this->assertFalse($pageResponse->viewData('pendingPayments')->contains('id', $invoice->id));
        $this->assertTrue($pageResponse->viewData('confirmedPayments')->contains('id', $invoice->id));

        // The Confirmed Payments table displays 'Juan Dela Cruz' and 'E-Wallet'
        $pageResponse->assertSee('Juan Dela Cruz');
        $pageResponse->assertSee('E-Wallet');
        $pageResponse->assertDontSee('<td>Ewallet</td>', false);
        $pageResponse->assertDontSee('<td>Gcash</td>', false);
    }

    public function test_processing_payment_with_cash_moves_guest_from_pending_to_confirmed_and_displays_cash(): void
    {
        $invoice = $this->createGuestBookingAndInvoice('Pedro', 'Penduko');

        // Initial state
        $initialResponse = $this->get(route('content.billing'));
        $this->assertTrue($initialResponse->viewData('pendingPayments')->contains('id', $invoice->id));
        $this->assertFalse($initialResponse->viewData('confirmedPayments')->contains('id', $invoice->id));

        // Process payment with Cash
        $response = $this->post(route('billing.processPayment', $invoice->id), [
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('content.billing'));

        // Moved to confirmed
        $pageResponse = $this->get(route('content.billing'));
        $pageResponse->assertOk();

        $this->assertFalse($pageResponse->viewData('pendingPayments')->contains('id', $invoice->id));
        $this->assertTrue($pageResponse->viewData('confirmedPayments')->contains('id', $invoice->id));

        $pageResponse->assertSee('Pedro Penduko');
        $pageResponse->assertSee('Cash');
    }

    public function test_processing_ewallet_payment_does_not_affect_existing_payment_records(): void
    {
        // Pre-existing paid cash invoice
        $existingCash = $this->createGuestBookingAndInvoice('ExistingCashGuest', 'One');
        $existingCash->update(['status' => 'paid']);
        PaymentLog::create([
            'invoice_id' => $existingCash->id,
            'amount_paid' => 600.00,
            'payment_method' => 'cash',
            'status' => 'successful',
        ]);

        // Pre-existing paid e-wallet invoice
        $existingEwallet = $this->createGuestBookingAndInvoice('ExistingEwalletGuest', 'Two');
        $existingEwallet->update(['status' => 'paid']);
        PaymentLog::create([
            'invoice_id' => $existingEwallet->id,
            'amount_paid' => 600.00,
            'payment_method' => 'ewallet',
            'status' => 'successful',
        ]);

        // New pending invoice to pay
        $newPending = $this->createGuestBookingAndInvoice('NewEwalletGuest', 'Three');

        $response = $this->post(route('billing.processPayment', $newPending->id), [
            'payment_method' => 'ewallet',
        ]);

        $response->assertRedirect(route('content.billing'));

        $pageResponse = $this->get(route('content.billing'));
        $pageResponse->assertOk();

        $confirmed = $pageResponse->viewData('confirmedPayments');
        $this->assertTrue($confirmed->contains('id', $existingCash->id));
        $this->assertTrue($confirmed->contains('id', $existingEwallet->id));
        $this->assertTrue($confirmed->contains('id', $newPending->id));

        // Both methods are correctly displayed for their respective records
        $pageResponse->assertSee('Cash');
        $pageResponse->assertSee('E-Wallet');
    }

    public function test_confirmed_payments_correctly_displays_ewallet_for_legacy_methods(): void
    {
        $invoice = $this->createGuestBookingAndInvoice('LegacyGuest', 'User');
        $invoice->update(['status' => 'paid']);

        PaymentLog::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 600.00,
            'payment_method' => 'gcash',
            'status' => 'successful',
        ]);

        $pageResponse = $this->get(route('content.billing'));
        $pageResponse->assertOk();

        // Legacy 'gcash' log should display as 'E-Wallet'
        $pageResponse->assertSee('E-Wallet');
        $pageResponse->assertDontSee('<td>Gcash</td>', false);
    }
}
