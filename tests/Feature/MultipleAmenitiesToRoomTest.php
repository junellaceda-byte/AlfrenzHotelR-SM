<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Charge;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleAmenitiesToRoomTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Guest $guest;

    private Booking $booking;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsReceptionist();

        $this->room = Room::create([
            'room_number' => '10',
            'short_stay_rate' => 800.00,
            'overnight_rate' => 2500.00,
            'status' => 'occupied',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'phone' => '09171234567',
        ]);

        $now = now()->setTimezone('Asia/Manila');

        $this->booking = Booking::create([
            'guest_id' => $this->guest->id,
            'phone' => '09171234567',
            'check_in_datetime' => $now->copy()->subHours(1),
            'check_out_datetime' => $now->copy()->addHours(5),
            'booked_rooms' => [$this->room->id],
            'stay_type' => 'overnight',
            'discount_type' => 'regular',
            'status' => 'checked_in',
        ]);

        // Base room charge
        Charge::create([
            'booking_id' => $this->booking->id,
            'type' => 'room',
            'description' => 'Room 10 - overnight stay',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'amount' => 2500.00,
            'is_paid' => false,
        ]);

        $this->invoice = Invoice::create([
            'booking_id' => $this->booking->id,
            'invoice_number' => 'INV-TEST101',
            'room_charges' => 2500.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 2500.00,
            'status' => 'unpaid',
            'due_date' => now()->toDateString(),
        ]);
    }

    public function test_multiple_amenities_can_be_added_to_room_in_one_transaction(): void
    {
        $amenity1 = Amenity::create([
            'name' => 'Extra Bed / Mat',
            'price' => 500.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $amenity2 = Amenity::create([
            'name' => 'Mini Bar Soda',
            'price' => 75.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $amenity3 = Amenity::create([
            'name' => 'Late Check-out Hour',
            'price' => 200.00,
            'charged' => 'hour',
            'offered' => true,
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'booking_id' => $this->booking->id,
            'amenities' => [
                [
                    'amenity_id' => $amenity1->id,
                    'quantity' => 2, // 2 * 500 = 1000
                    'notes' => 'For kids',
                ],
                [
                    'amenity_id' => $amenity2->id,
                    'quantity' => 4, // 4 * 75 = 300
                    'notes' => 'Chilled',
                ],
                [
                    'amenity_id' => $amenity3->id,
                    'quantity' => 1, // 1 * 200 = 200
                    'notes' => 'Until 1 PM',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check charges created
        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'type' => 'amenity',
            'description' => 'Extra Bed / Mat (For kids)',
            'quantity' => 2,
            'unit_price' => 500.00,
            'amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'type' => 'amenity',
            'description' => 'Mini Bar Soda (Chilled)',
            'quantity' => 4,
            'unit_price' => 75.00,
            'amount' => 300.00,
        ]);

        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'type' => 'amenity',
            'description' => 'Late Check-out Hour (Until 1 PM)',
            'quantity' => 1,
            'unit_price' => 200.00,
            'amount' => 200.00,
        ]);

        // Check available_amenities created
        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'invoice_id' => $this->invoice->id,
            'item_name' => 'Extra Bed / Mat (For kids)',
            'quantity' => 2,
            'unit_price' => 500.00,
            'total_amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'invoice_id' => $this->invoice->id,
            'item_name' => 'Mini Bar Soda (Chilled)',
            'quantity' => 4,
            'unit_price' => 75.00,
            'total_amount' => 300.00,
        ]);

        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'invoice_id' => $this->invoice->id,
            'item_name' => 'Late Check-out Hour (Until 1 PM)',
            'quantity' => 1,
            'unit_price' => 200.00,
            'total_amount' => 200.00,
        ]);

        // Total extra charges: 1000 + 300 + 200 = 1500
        // Grand total: 2500 (room) + 1500 (amenities) = 4000
        $this->invoice->refresh();
        $this->assertEquals(1500.00, (float) $this->invoice->extra_charges_total);
        $this->assertEquals(4000.00, (float) $this->invoice->grand_total);
    }

    public function test_multiple_amenities_can_be_added_via_json_payload(): void
    {
        $amenity1 = Amenity::create([
            'name' => 'Bath Robe',
            'price' => 150.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $amenity2 = Amenity::create([
            'name' => 'Slippers',
            'price' => 50.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $jsonPayload = json_encode([
            ['amenity_id' => $amenity1->id, 'quantity' => 2, 'notes' => 'White'],
            ['amenity_id' => $amenity2->id, 'quantity' => 2, 'notes' => ''],
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'booking_id' => $this->booking->id,
            'amenities_json' => $jsonPayload,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Bath Robe (White)',
            'quantity' => 2,
            'amount' => 300.00,
        ]);

        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Slippers',
            'quantity' => 2,
            'amount' => 100.00,
        ]);

        $this->invoice->refresh();
        $this->assertEquals(400.00, (float) $this->invoice->extra_charges_total);
        $this->assertEquals(2900.00, (float) $this->invoice->grand_total);
    }

    public function test_modal_view_contains_amenities_list_beside_choose_amenity_dropdown(): void
    {
        Amenity::create([
            'name' => 'Pool Towel',
            'price' => 100.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();

        // CHOOSE AMENITY dropdown label and element
        $response->assertSee('CHOOSE AMENITY');
        $response->assertSee('id="amenityItemSelect"', false);

        // Beside it: Selected Amenities List and related elements
        $response->assertSee('SELECTED AMENITIES LIST');
        $response->assertSee('id="selectedAmenitiesList"', false);
        $response->assertSee('id="selectedAmenitiesTotal"', false);
        $response->assertSee('id="selectedAmenitiesBadge"', false);

        // Removed elements must not be present in the modal
        $response->assertDontSee('id="btnAddAmenityToList"', false);
        $response->assertDontSee('id="amenityQuantity"', false);
        $response->assertDontSee('id="amenitySpecialReq"', false);
        $response->assertDontSee('QUANTITY (beds or hours)', false);
        $response->assertDontSee('NOTES / TIME', false);
    }

    public function test_adding_multiple_amenities_fails_atomically_if_any_is_not_offered(): void
    {
        $offeredAmenity = Amenity::create([
            'name' => 'Valid Amenity',
            'price' => 100.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $unofferedAmenity = Amenity::create([
            'name' => 'Discontinued Amenity',
            'price' => 500.00,
            'charged' => 'item',
            'offered' => false,
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'booking_id' => $this->booking->id,
            'amenities' => [
                ['amenity_id' => $offeredAmenity->id, 'quantity' => 1],
                ['amenity_id' => $unofferedAmenity->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('amenity_id');

        // Verify atomic rollback - no charges created
        $this->assertDatabaseMissing('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Valid Amenity',
        ]);
        $this->assertDatabaseMissing('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Discontinued Amenity',
        ]);
    }

    public function test_single_amenity_addition_still_works_seamlessly(): void
    {
        $amenity = Amenity::create([
            'name' => 'Single Amenity Test',
            'price' => 250.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'amenity_id' => $amenity->id,
            'quantity' => 3,
            'notes' => 'Fast delivery',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'type' => 'amenity',
            'description' => 'Single Amenity Test (Fast delivery)',
            'quantity' => 3,
            'unit_price' => 250.00,
            'amount' => 750.00,
        ]);

        $this->invoice->refresh();
        $this->assertEquals(750.00, (float) $this->invoice->extra_charges_total);
        $this->assertEquals(3250.00, (float) $this->invoice->grand_total);
    }
}
