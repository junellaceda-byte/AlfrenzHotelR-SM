<?php

namespace Tests\Feature;

use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_amenity_table_shows_description_and_status_column_headers(): void
    {
        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        $response->assertSee('Description');
        $response->assertSee('Status');
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-start">Charged</th>', false);
        $response->assertDontSee('<th class="py-2.5 py-sm-3 px-2 px-sm-3 px-md-4 text-center">Offered</th>', false);
    }

    public function test_amenity_table_displays_exact_custom_description(): void
    {
        Amenity::create([
            'name' => 'Poolside Towel',
            'price' => 150.00,
            'charged' => 'per use',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        $response->assertSee('per use');
        // Should NOT prepend "per " prefix to the stored value
        $response->assertDontSee('>per per use<', false);
    }

    public function test_add_amenity_modal_has_description_text_input_not_select(): void
    {
        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        // Label renamed to "Description"
        $response->assertSee('Description');
        // No dropdown with predefined options
        $response->assertDontSee('<option value="person">person</option>', false);
        $response->assertDontSee('<option value="item">item</option>', false);
        $response->assertDontSee('<option value="hours">hours</option>', false);
        $response->assertDontSee('<option value="night">night</option>', false);
        // Free-text input is present for the charged field
        $response->assertSee('name="charged"', false);
        $response->assertSee('type="text"', false);
    }

    public function test_edit_amenity_modal_has_description_text_input_with_saved_value(): void
    {
        $amenity = Amenity::create([
            'name' => 'Breakfast Set',
            'price' => 350.00,
            'charged' => 'per meal',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        // Saved custom description pre-filled in the edit input
        $response->assertSee('value="per meal"', false);
        // Label shows "Description" not "Amenity charged per"
        $response->assertDontSee('Amenity charged per');
        // Status label (not "Offered Status")
        $response->assertDontSee('Offered Status');
    }

    public function test_store_amenity_saves_custom_description_to_database(): void
    {
        $response = $this->post('/amenities/store', [
            'name' => 'Massage Session',
            'price' => 500.00,
            'charged' => 'per session',
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $this->assertDatabaseHas('amenities', [
            'name' => 'Massage Session',
            'charged' => 'per session',
        ]);
    }

    public function test_store_amenity_rejects_empty_description(): void
    {
        $response = $this->post('/amenities/store', [
            'name' => 'Sauna',
            'price' => 200.00,
            'charged' => '',
        ]);

        $response->assertSessionHasErrors('charged');
        $this->assertDatabaseMissing('amenities', ['name' => 'Sauna']);
    }

    public function test_update_amenity_saves_edited_description_to_database(): void
    {
        $amenity = Amenity::create([
            'name' => 'Gym Access',
            'price' => 100.00,
            'charged' => 'per day',
            'offered' => true,
        ]);

        $response = $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Gym Access',
            'price' => 100.00,
            'charged' => 'per visit',
            'offered' => '1',
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $amenity->refresh();
        $this->assertSame('per visit', $amenity->charged);
    }

    public function test_update_amenity_reflects_new_description_in_table(): void
    {
        $amenity = Amenity::create([
            'name' => 'Bike Rental',
            'price' => 80.00,
            'charged' => 'per half-day',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();
        $response->assertSee('per half-day');

        // Update to a new description
        $this->put("/amenities/{$amenity->id}/update", [
            'name' => 'Bike Rental',
            'price' => 80.00,
            'charged' => 'per full-day',
            'offered' => '1',
        ]);

        $amenity->refresh();
        $this->assertSame('per full-day', $amenity->charged);

        $viewResponse = $this->get('/admindashboard?section=amenity');
        $viewResponse->assertOk();
        $viewResponse->assertSee('per full-day');
    }

    public function test_custom_descriptions_beyond_predefined_values_are_accepted(): void
    {
        $response = $this->post('/amenities/store', [
            'name' => 'Airport Transfer',
            'price' => 1200.00,
            'charged' => 'per trip (one way)',
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $this->assertDatabaseHas('amenities', [
            'name' => 'Airport Transfer',
            'charged' => 'per trip (one way)',
        ]);
    }
}
