<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAmenityToggleAndImageRemoveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_amenities_table_renders_interactive_status_toggle_switch(): void
    {
        $offeredAmenity = Amenity::create([
            'name' => 'Extra Pillow',
            'price' => 80.00,
            'charged' => 'per piece',
            'offered' => true,
        ]);

        $notOfferedAmenity = Amenity::create([
            'name' => 'Pool Towel',
            'price' => 50.00,
            'charged' => 'per piece',
            'offered' => false,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        $response->assertSee('amenity-toggle-switch', false);
        $response->assertSee('data-id="'.$offeredAmenity->id.'"', false);
        $response->assertSee('data-id="'.$notOfferedAmenity->id.'"', false);
        $response->assertSee('aria-label="Toggle Extra Pillow status"', false);
        $response->assertDontSee('aria-disabled="true"', false);
    }

    public function test_admin_can_toggle_amenity_status_via_json_endpoint(): void
    {
        $amenity = Amenity::create([
            'name' => 'Ironing Board',
            'price' => 100.00,
            'charged' => 'per stay',
            'offered' => true,
        ]);

        $responseOff = $this->postJson("/amenities/{$amenity->id}/toggle");
        $responseOff->assertOk()->assertJson([
            'status' => 'success',
            'offered' => false,
        ]);
        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'offered' => 0,
        ]);

        $responseOn = $this->postJson("/amenities/{$amenity->id}/toggle");
        $responseOn->assertOk()->assertJson([
            'status' => 'success',
            'offered' => true,
        ]);
        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'offered' => 1,
        ]);
    }

    public function test_add_and_edit_amenity_modals_include_hidden_remove_image_buttons(): void
    {
        $amenity = Amenity::create([
            'name' => 'Slippers',
            'price' => 60.00,
            'charged' => 'per pair',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        $response->assertSee('id="removeAddAmenityImageBtn"', false);
        $response->assertSee('id="removeEditAmenityImageBtn'.$amenity->id.'"', false);
        $response->assertSee('data-original-src="', false);
        $response->assertSee('removeEditAmenityImage(', false);
    }

    public function test_amenity_toggle_handle_css_has_circular_border_radius(): void
    {
        $css = file_get_contents(public_path('CSS/Administrator/amenity.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('.amenity-toggle-switch::before', $css);
        $this->assertStringContainsString('border-radius: 50%;', $css);
    }

    public function test_edit_amenity_modal_does_not_render_status_option_and_update_preserves_offered(): void
    {
        $amenity = Amenity::create([
            'name' => 'Kettle',
            'price' => 150.00,
            'charged' => 'per stay',
            'offered' => false,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();
        $response->assertDontSee('name="offered"', false);
        $response->assertDontSee('On (Being Offered)');
        $response->assertDontSee('Off (Not Offered)');

        $updateResponse = $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Electric Kettle',
            'price' => 175.00,
            'charged' => 'per stay',
        ]);

        $updateResponse->assertRedirect('/admindashboard?section=amenity');
        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'name' => 'Electric Kettle',
            'price' => 175.00,
            'offered' => 0,
        ]);
    }

    public function test_status_toggle_controls_amenity_visibility_on_receptionist_side(): void
    {
        $room = Room::create([
            'room_number' => '01',
            'short_stay_rate' => 600.00,
            'overnight_rate' => 2200.00,
            'status' => 'occupied',
        ]);

        $guest = Guest::create([
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'phone' => '09171234567',
        ]);

        $now = now()->setTimezone('Asia/Manila');

        Booking::create([
            'guest_id' => $guest->id,
            'phone' => '09171234567',
            'check_in_datetime' => $now->copy()->subHour(),
            'check_out_datetime' => $now->copy()->addHours(5),
            'booked_rooms' => [$room->id],
            'status' => 'checked_in',
            'stay_type' => 'short_stay',
            'short_stay_hours' => 6,
        ]);

        $amenity = Amenity::create([
            'name' => 'Hair Dryer',
            'price' => 90.00,
            'charged' => 'per stay',
            'offered' => true,
        ]);

        // Initially ON -> visible on Receptionist dashboard and /api/amenities
        $this->actingAsReceptionist();
        $this->get('/dashboardreceptionist')->assertOk()->assertSee('Hair Dryer');
        $this->getJson(route('api.amenities'))->assertOk()->assertSee('Hair Dryer');

        // Admin toggles OFF -> hidden from Receptionist dashboard and /api/amenities
        $this->actingAsAdmin();
        $this->postJson("/amenities/{$amenity->id}/toggle")->assertOk()->assertJson(['offered' => false]);

        $this->actingAsReceptionist();
        $this->get('/dashboardreceptionist')->assertOk()->assertDontSee('Hair Dryer');
        $this->getJson(route('api.amenities'))->assertOk()->assertDontSee('Hair Dryer');

        // Admin toggles ON -> visible again on Receptionist side
        $this->actingAsAdmin();
        $this->postJson("/amenities/{$amenity->id}/toggle")->assertOk()->assertJson(['offered' => true]);

        $this->actingAsReceptionist();
        $this->get('/dashboardreceptionist')->assertOk()->assertSee('Hair Dryer');
        $this->getJson(route('api.amenities'))->assertOk()->assertSee('Hair Dryer');
    }
}
