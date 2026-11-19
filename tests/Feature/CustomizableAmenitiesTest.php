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

class CustomizableAmenitiesTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Guest $guest;

    private Booking $booking;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create([
            'room_number' => '05',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'occupied',
        ]);

        $this->guest = Guest::create([
            'fname' => 'Maria',
            'lname' => 'Santos',
            'phone' => '09123456789',
        ]);

        $now = now()->setTimezone('Asia/Manila');

        $this->booking = Booking::create([
            'guest_id' => $this->guest->id,
            'phone' => '09123456789',
            'check_in_datetime' => $now->copy()->subHours(2),
            'check_out_datetime' => $now->copy()->addHours(4),
            'booked_rooms' => [$this->room->id],
            'status' => 'checked_in',
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
        ]);

        $this->invoice = Invoice::create([
            'booking_id' => $this->booking->id,
            'invoice_number' => 'INV-TEST-001',
            'room_charges' => 600.00,
            'extra_charges_total' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 600.00,
            'status' => 'unpaid',
            'due_date' => $now->toDateString(),
        ]);

        Charge::create([
            'booking_id' => $this->booking->id,
            'type' => 'room',
            'description' => 'Room 05 - 6-hour short stay',
            'quantity' => 1,
            'unit_price' => 600.00,
            'amount' => 600.00,
        ]);
    }

    public function test_admin_can_view_amenities_in_amenities_section(): void
    {
        Amenity::create([
            'name' => 'Premium Slippers',
            'price' => 75.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();
        $response->assertSee('Available Amenities');
        $response->assertSee('Premium Slippers');
        $response->assertSee('₱75.00');
    }

    public function test_admin_can_create_new_amenity(): void
    {
        $response = $this->post('/amenities/store', [
            'name' => 'Bathrobe',
            'price' => 250.00,
            'charged' => 'item',
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'name' => 'Bathrobe',
            'price' => 250.00,
            'charged' => 'item',
            'offered' => 1,
        ]);
    }

    public function test_admin_can_update_amenity(): void
    {
        $amenity = Amenity::create([
            'name' => 'Toothbrush Kit',
            'price' => 40.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Deluxe Dental Kit',
            'price' => 60.00,
            'charged' => 'item',
            'offered' => 0,
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'name' => 'Deluxe Dental Kit',
            'price' => 60.00,
            'offered' => 0,
        ]);
    }

    public function test_admin_can_delete_amenity(): void
    {
        $amenity = Amenity::create([
            'name' => 'Disposable Razor',
            'price' => 35.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->delete("/amenities/{$amenity->id}/delete");

        $response->assertRedirect('/admindashboard?section=amenity');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('amenities', [
            'id' => $amenity->id,
        ]);
    }

    public function test_receptionist_sees_customizable_amenities_in_add_amenities_modal(): void
    {
        $amenity = Amenity::create([
            'name' => 'Extra Towel Set',
            'price' => 120.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();
        $response->assertSee('Extra Towel Set');
        $response->assertSee('₱120.00');
    }

    public function test_adding_amenity_to_occupied_room_records_charges_and_billing(): void
    {
        $amenity = Amenity::create([
            'name' => 'Extra Bed / Mat',
            'price' => 500.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'amenity_id' => $amenity->id,
            'quantity' => 2,
            'notes' => 'ASAP please',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify charge is created
        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'type' => 'amenity',
            'quantity' => 2,
            'unit_price' => 500.00,
            'amount' => 1000.00,
        ]);

        // Verify available_amenity is created for invoice
        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'invoice_id' => $this->invoice->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'total_amount' => 1000.00,
        ]);

        // Verify invoice was updated
        $this->invoice->refresh();
        $this->assertEquals(1000.00, $this->invoice->extra_charges_total);
        $this->assertEquals(1600.00, $this->invoice->grand_total);
    }

    public function test_newly_added_amenity_with_status_on_appears_in_receptionist_dropdown(): void
    {
        // Admin adds a new amenity with status On (offered = true)
        $this->post('/amenities/store', [
            'name' => 'Luxury Robe',
            'price' => 350.00,
            'charged' => 'item',
        ]);

        $this->assertDatabaseHas('amenities', [
            'name' => 'Luxury Robe',
            'offered' => 1,
        ]);

        // Receptionist dashboard CHOOSE AMENITY dropdown automatically shows it
        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();
        $response->assertSee('Luxury Robe');
        $response->assertSee('350.00');
    }

    public function test_edited_amenity_updates_in_receptionist_dropdown(): void
    {
        $amenity = Amenity::create([
            'name' => 'Basic Slippers',
            'price' => 50.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        // Verify initial state appears
        $response1 = $this->get('/dashboardreceptionist');
        $response1->assertSee('Basic Slippers');

        // Admin updates name and price
        $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Plush Velvet Slippers',
            'price' => 120.00,
            'charged' => 'item',
            'offered' => 1,
        ]);

        // Receptionist dashboard CHOOSE AMENITY dropdown automatically reflects updated details
        $response2 = $this->get('/dashboardreceptionist');
        $response2->assertOk();
        $response2->assertSee('Plush Velvet Slippers');
        $response2->assertSee('120.00');
        $response2->assertDontSee('Basic Slippers');
    }

    public function test_amenity_marked_off_not_offered_disappears_from_receptionist_dropdown(): void
    {
        $amenity = Amenity::create([
            'name' => 'Pool Towel',
            'price' => 80.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        // Initially visible
        $this->get('/dashboardreceptionist')->assertSee('Pool Towel');

        // Admin marks amenity Off (Not Offered)
        $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Pool Towel',
            'price' => 80.00,
            'charged' => 'item',
            'offered' => 0,
        ]);

        // Receptionist CHOOSE AMENITY dropdown no longer displays it
        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();
        $response->assertDontSee('Pool Towel');
    }

    public function test_deleted_amenity_disappears_from_receptionist_dropdown(): void
    {
        $amenity = Amenity::create([
            'name' => 'Morning Newspaper',
            'price' => 45.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        // Initially visible
        $this->get('/dashboardreceptionist')->assertSee('Morning Newspaper');

        // Admin deletes amenity
        $this->delete("/amenities/{$amenity->id}/delete");

        // Receptionist CHOOSE AMENITY dropdown no longer displays it
        $response = $this->get('/dashboardreceptionist');
        $response->assertOk();
        $response->assertDontSee('Morning Newspaper');
    }

    public function test_amenity_status_change_or_deletion_does_not_affect_existing_guest_records_and_billing(): void
    {
        $amenity = Amenity::create([
            'name' => 'Mini Bar Soda',
            'price' => 70.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        // Add amenity to the guest's room
        $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'amenity_id' => $amenity->id,
            'quantity' => 2,
        ]);

        // Charge and AvailableAmenity records created (2 x 70 = 140)
        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Mini Bar Soda',
            'amount' => 140.00,
        ]);
        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'item_name' => 'Mini Bar Soda',
            'total_amount' => 140.00,
        ]);

        $this->invoice->refresh();
        $this->assertEquals(140.00, $this->invoice->extra_charges_total);
        $this->assertEquals(740.00, $this->invoice->grand_total);

        // Admin now changes the amenity to Off (Not Offered)
        $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Mini Bar Soda',
            'price' => 99.00,
            'charged' => 'item',
            'offered' => 0,
        ]);

        // Existing guest records and invoice totals remain unchanged
        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Mini Bar Soda',
            'amount' => 140.00,
        ]);
        $this->invoice->refresh();
        $this->assertEquals(140.00, $this->invoice->extra_charges_total);
        $this->assertEquals(740.00, $this->invoice->grand_total);

        // Admin now deletes the amenity completely
        $this->delete("/amenities/{$amenity->id}/delete");

        // Existing guest billing and charge history are STILL intact
        $this->assertDatabaseHas('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Mini Bar Soda',
            'amount' => 140.00,
        ]);
        $this->assertDatabaseHas('available_amenities', [
            'booking_id' => $this->booking->id,
            'item_name' => 'Mini Bar Soda',
            'total_amount' => 140.00,
        ]);
        $this->invoice->refresh();
        $this->assertEquals(140.00, $this->invoice->extra_charges_total);
        $this->assertEquals(740.00, $this->invoice->grand_total);
    }

    public function test_cannot_add_unoffered_amenity_to_room(): void
    {
        $amenity = Amenity::create([
            'name' => 'Seasonal Ski Gear',
            'price' => 500.00,
            'charged' => 'item',
            'offered' => false,
        ]);

        $response = $this->post(route('booking.addAmenityToRoom'), [
            'room_id' => $this->room->id,
            'amenity_id' => $amenity->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('amenity_id');
        $this->assertDatabaseMissing('charges', [
            'booking_id' => $this->booking->id,
            'description' => 'Seasonal Ski Gear',
        ]);
    }

    public function test_realtime_api_amenities_returns_offered_amenities(): void
    {
        Amenity::create([
            'name' => 'Free Wi-Fi Card',
            'price' => 0.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        Amenity::create([
            'name' => 'Defunct Service',
            'price' => 50.00,
            'charged' => 'item',
            'offered' => false,
        ]);

        $response = $this->getJson('/api/amenities');
        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonFragment(['name' => 'Free Wi-Fi Card']);
        $response->assertJsonMissing(['name' => 'Defunct Service']);
    }

    public function test_realtime_api_amenities_syncs_on_add_edit_toggle_delete(): void
    {
        // 1. Add amenity
        $addRes = $this->postJson('/amenities/store', [
            'name' => 'Espresso Machine',
            'price' => 150.00,
            'charged' => 'item',
        ]);
        $addRes->assertOk();

        $apiRes1 = $this->getJson('/api/amenities');
        $apiRes1->assertJsonFragment(['name' => 'Espresso Machine', 'price' => 150.0]);

        $amenityId = Amenity::where('name', 'Espresso Machine')->value('id');

        // 2. Edit amenity
        $editRes = $this->putJson("/amenities/{$amenityId}/update", [
            'name' => 'Premium Espresso Bar',
            'price' => 220.00,
            'charged' => 'item',
            'offered' => 1,
        ]);
        $editRes->assertOk();

        $apiRes2 = $this->getJson('/api/amenities');
        $apiRes2->assertJsonFragment(['name' => 'Premium Espresso Bar', 'price' => 220.0]);
        $apiRes2->assertJsonMissing(['name' => 'Espresso Machine']);

        // 3. Toggle off (disable)
        $toggleRes1 = $this->postJson("/amenities/{$amenityId}/toggle");
        $toggleRes1->assertOk();
        $this->assertFalse((bool) Amenity::find($amenityId)->offered);

        $apiRes3 = $this->getJson('/api/amenities');
        $apiRes3->assertJsonMissing(['name' => 'Premium Espresso Bar']);

        // 4. Toggle back on (enable)
        $toggleRes2 = $this->postJson("/amenities/{$amenityId}/toggle");
        $toggleRes2->assertOk();
        $this->assertTrue((bool) Amenity::find($amenityId)->offered);

        $apiRes4 = $this->getJson('/api/amenities');
        $apiRes4->assertJsonFragment(['name' => 'Premium Espresso Bar']);

        // 5. Delete amenity
        $delRes = $this->deleteJson("/amenities/{$amenityId}/delete");
        $delRes->assertOk();

        $apiRes5 = $this->getJson('/api/amenities');
        $apiRes5->assertJsonMissing(['name' => 'Premium Espresso Bar']);
    }

    public function test_dashboard_notifications_endpoint_includes_amenities(): void
    {
        Amenity::create([
            'name' => 'Live Synced Amenity',
            'price' => 95.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->getJson('/api/dashboard/notifications');
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Live Synced Amenity']);
    }

    public function test_offered_column_is_non_toggleable_directly_from_table_and_updated_via_edit_modal(): void
    {
        $amenity = Amenity::create([
            'name' => 'Modal Editable Amenity',
            'price' => 180.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();

        // 1. Offered switch in table is non-toggleable (disabled, tabindex="-1", aria-disabled="true")
        $response->assertSee('class="form-check-input amenity-toggle-switch" type="checkbox" role="switch" data-id="'.$amenity->id.'" checked disabled tabindex="-1" aria-disabled="true"', false);

        // 2. Edit Amenity modal contains offered status selection
        $response->assertSee('id="editAmenityModal'.$amenity->id.'"', false);
        $response->assertSee('name="offered"', false);
        $response->assertSee('On (Being Offered)');
        $response->assertSee('Off (Not Offered)');

        // 3. Status can only be changed through Edit Amenity submission
        $updateResponse = $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Modal Editable Amenity',
            'price' => 180.00,
            'charged' => 'item',
            'offered' => 0,
        ]);

        $updateResponse->assertRedirect('/admindashboard?section=amenity');
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'offered' => 0,
        ]);

        // 4. Verify rendered table reflects the updated unchecked non-toggleable switch
        $updatedViewResponse = $this->get('/admindashboard?section=amenity');
        $updatedViewResponse->assertOk();
        $updatedViewResponse->assertSee('class="form-check-input amenity-toggle-switch" type="checkbox" role="switch" data-id="'.$amenity->id.'"  disabled tabindex="-1" aria-disabled="true"', false);
    }
}
