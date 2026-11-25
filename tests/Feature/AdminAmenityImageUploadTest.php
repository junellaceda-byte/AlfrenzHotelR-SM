<?php

namespace Tests\Feature;

use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminAmenityImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    protected function tearDown(): void
    {
        // Clean up any test images generated in public/images/amenities
        $amenitiesPath = public_path('images/amenities');
        if (File::exists($amenitiesPath)) {
            $files = File::files($amenitiesPath);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'amenity_') || str_starts_with($file->getFilename(), 'test_')) {
                    @unlink($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    private function createFakeImage(string $filename = 'test_cube.png'): UploadedFile
    {
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($filename, $png1x1);
    }

    public function test_amenity_table_displays_cube_image_thumbnail_with_tooltip_and_preserves_name_in_database(): void
    {
        $amenity = Amenity::create([
            'name' => 'Premium Slippers',
            'price' => 120.00,
            'charged' => 'per pair',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        // Cube image thumbnail is rendered
        $response->assertSee('amenity-cube-thumb', false);
        // Tooltip contains the actual amenity name
        $response->assertSee('data-bs-toggle="tooltip"', false);
        $response->assertSee('title="Premium Slippers"', false);
        // Table does not display raw text name replacing the cube
        $response->assertDontSee('<span class="amenity-name fw-semibold text-dark">Premium Slippers</span>', false);

        // Name is preserved in the database
        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'name' => 'Premium Slippers',
        ]);
    }

    public function test_add_amenity_modal_contains_image_upload_and_cube_preview_and_retains_name_field(): void
    {
        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        // Add form has multipart enctype
        $response->assertSee('action="/amenities/store"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
        // Local image upload file input
        $response->assertSee('id="addAmenityImage"', false);
        $response->assertSee('name="image"', false);
        // Image preview element with default placeholder
        $response->assertSee('id="addAmenityPreview"', false);
        $response->assertSee('default-amenity.svg', false);
        // Retains Amenity Name input field
        $response->assertSee('name="name"', false);
        $response->assertSee('Amenity name');
    }

    public function test_edit_amenity_modal_displays_current_image_and_replacement_upload_and_saved_name(): void
    {
        $amenity = Amenity::create([
            'name' => 'Dental Kit',
            'price' => 45.00,
            'charged' => 'per kit',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');

        $response->assertOk();
        // Edit form has multipart enctype
        $response->assertSee('action="/amenities/'.$amenity->id.'/update"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
        // Displays currently uploaded / default amenity image preview
        $response->assertSee('id="editAmenityPreview'.$amenity->id.'"', false);
        $response->assertSee($amenity->image_url, false);
        // Replacement image upload input
        $response->assertSee('id="editAmenityImage'.$amenity->id.'"', false);
        // Retains Amenity Name input field with saved name
        $response->assertSee('value="Dental Kit"', false);
    }

    public function test_store_amenity_with_uploaded_image_saves_file_and_associates_path(): void
    {
        $fakeImage = $this->createFakeImage('cube_test.png');

        $response = $this->post('/amenities/store', [
            'name' => 'Bathrobe Luxury',
            'price' => 250.00,
            'charged' => 'per stay',
            'image' => $fakeImage,
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $amenity = Amenity::where('name', 'Bathrobe Luxury')->first();
        $this->assertNotNull($amenity);
        $this->assertNotNull($amenity->image);
        $this->assertStringStartsWith('images/amenities/', $amenity->image);
        $this->assertTrue(File::exists(public_path($amenity->image)));

        // image_url resolves properly
        $this->assertStringContainsString('images/amenities/', $amenity->image_url);
    }

    public function test_store_amenity_without_image_uses_default_cube_svg_placeholder(): void
    {
        $response = $this->post('/amenities/store', [
            'name' => 'Extra Pillow',
            'price' => 80.00,
            'charged' => 'per piece',
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $amenity = Amenity::where('name', 'Extra Pillow')->first();
        $this->assertNotNull($amenity);
        $this->assertNull($amenity->image);
        // image_url attribute resolves to default placeholder
        $this->assertStringContainsString('default-amenity.svg', $amenity->image_url);
    }

    public function test_update_amenity_with_replacement_image_replaces_old_image(): void
    {
        $initialImage = $this->createFakeImage('initial_cube.png');

        $this->post('/amenities/store', [
            'name' => 'Electric Kettle',
            'price' => 150.00,
            'charged' => 'per use',
            'image' => $initialImage,
        ]);

        $amenity = Amenity::where('name', 'Electric Kettle')->firstOrFail();
        $initialImagePath = public_path($amenity->image);
        $this->assertTrue(File::exists($initialImagePath));

        $replacementImage = $this->createFakeImage('replacement_cube.png');

        $response = $this->put('/amenities/'.$amenity->id.'/update', [
            'name' => 'Electric Kettle Pro',
            'price' => 180.00,
            'charged' => 'per use',
            'offered' => 1,
            'image' => $replacementImage,
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $amenity->refresh();
        $this->assertEquals('Electric Kettle Pro', $amenity->name);
        $this->assertNotEquals($initialImagePath, public_path($amenity->image));
        $this->assertTrue(File::exists(public_path($amenity->image)));
        // Old image file was cleaned up
        $this->assertFalse(File::exists($initialImagePath));
    }

    public function test_update_amenity_without_new_image_retains_previously_saved_image(): void
    {
        $image = $this->createFakeImage('existing_cube.png');

        $this->post('/amenities/store', [
            'name' => 'Coffee Maker',
            'price' => 200.00,
            'charged' => 'per night',
            'image' => $image,
        ]);

        $amenity = Amenity::where('name', 'Coffee Maker')->firstOrFail();
        $savedImagePath = $amenity->image;

        $response = $this->put('/amenities/'.$amenity->id.'/update', [
            'name' => 'Coffee Maker Deluxe',
            'price' => 220.00,
            'charged' => 'per night',
            'offered' => 1,
        ]);

        $response->assertRedirect('/admindashboard?section=amenity');

        $amenity->refresh();
        $this->assertEquals('Coffee Maker Deluxe', $amenity->name);
        $this->assertEquals($savedImagePath, $amenity->image);
        $this->assertTrue(File::exists(public_path($amenity->image)));
    }

    public function test_store_amenity_validates_image_file_type(): void
    {
        $fakeFile = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->post('/amenities/store', [
            'name' => 'Invalid File Amenity',
            'price' => 50.00,
            'charged' => 'per item',
            'image' => $fakeFile,
        ]);

        $response->assertSessionHasErrors(['image']);
        $this->assertDatabaseMissing('amenities', [
            'name' => 'Invalid File Amenity',
        ]);
    }

    public function test_receptionist_side_continues_retrieving_correct_amenity_data(): void
    {
        Amenity::create([
            'name' => 'Room Humidifier',
            'price' => 150.00,
            'charged' => 'per day',
            'offered' => true,
        ]);

        $response = $this->getJson('/api/amenities');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertJsonFragment([
            'name' => 'Room Humidifier',
            'item_name' => 'Room Humidifier',
            'price' => 150.0,
            'charged' => 'per day',
            'offered' => true,
        ]);
    }
}
