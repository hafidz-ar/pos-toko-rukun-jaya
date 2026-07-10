<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery\MockInterface;

class CloudinaryProductPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Category $category;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner Ali',
            'username' => 'owner_ali',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->category = Category::create(['name' => 'Bahan Bangunan']);
        $this->unit = Unit::create(['name' => 'sak']);
    }

    /**
     * Test creating a product with valid image file.
     */
    public function test_create_product_with_valid_image_uploads_to_cloudinary()
    {
        $this->actingAs($this->owner);

        // Mock CloudinaryService
        $this->mock(\App\Services\CloudinaryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('upload')
                ->once()
                ->with(\Mockery::type(UploadedFile::class))
                ->andReturn([
                    'secure_url' => 'https://res.cloudinary.com/dummy/image/upload/v12345/pos-toko/products/test.png',
                    'public_id' => 'pos-toko/products/test_public_id',
                ]);
        });

        Storage::fake('local');
        $file = UploadedFile::fake()->image('cement.png');

        $payload = [
            'name' => 'Semen Gresik 50kg',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 50000,
            'selling_price_per_base_unit' => 60000,
            'stock_qty_base_unit' => 10,
            'location' => 'Rak A1',
            'photo_file' => $file,
            'min_stock_threshold' => 5,
        ];

        $response = $this->post('/products', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Semen Gresik 50kg',
            'photo_url' => 'https://res.cloudinary.com/dummy/image/upload/v12345/pos-toko/products/test.png',
            'photo_public_id' => 'pos-toko/products/test_public_id',
        ]);
    }

    /**
     * Test updating a product with new image file triggers deletion of old image.
     */
    public function test_update_product_with_new_image_deletes_old_image()
    {
        $this->actingAs($this->owner);

        $product = Product::create([
            'name' => 'Semen Gresik 50kg',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 50000,
            'selling_price_per_base_unit' => 60000,
            'stock_qty_base_unit' => 10,
            'photo_url' => 'https://res.cloudinary.com/dummy/image/upload/v12345/pos-toko/products/old.png',
            'photo_public_id' => 'pos-toko/products/old_public_id',
        ]);

        // Mock CloudinaryService to upload the replacement before deleting the old image
        $this->mock(\App\Services\CloudinaryService::class, function (MockInterface $mock) use ($product) {
            $mock->shouldReceive('upload')
                ->once()
                ->with(\Mockery::type(UploadedFile::class))
                ->andReturn([
                    'secure_url' => 'https://res.cloudinary.com/dummy/image/upload/v12345/pos-toko/products/new.png',
                    'public_id' => 'pos-toko/products/new_public_id',
                ]);

            $mock->shouldReceive('delete')
                ->once()
                ->with($product->photo_public_id);
        });

        Storage::fake('local');
        $file = UploadedFile::fake()->image('cement_new.png');

        $payload = [
            'name' => 'Semen Gresik 50kg (Updated)',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'selling_price_per_base_unit' => 65000,
            'photo_file' => $file,
        ];

        $response = $this->put("/products/{$product->id}", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Semen Gresik 50kg (Updated)',
            'photo_url' => 'https://res.cloudinary.com/dummy/image/upload/v12345/pos-toko/products/new.png',
            'photo_public_id' => 'pos-toko/products/new_public_id',
        ]);
    }

    /**
     * Test validation rules reject invalid file formats (e.g. pdf, txt).
     */
    public function test_rejects_invalid_photo_file_formats()
    {
        $this->actingAs($this->owner);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $payload = [
            'name' => 'Semen Gresik 50kg',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 50000,
            'selling_price_per_base_unit' => 60000,
            'photo_file' => $file,
        ];

        $response = $this->post('/products', $payload);

        $response->assertSessionHasErrors(['photo_file']);
        $this->assertDatabaseMissing('products', [
            'name' => 'Semen Gresik 50kg',
        ]);
    }

    /**
     * Test that product can be created/updated without photo file.
     */
    public function test_product_crud_works_without_photo_file()
    {
        $this->actingAs($this->owner);

        $payload = [
            'name' => 'Besi Beton 8mm',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 45000,
            'selling_price_per_base_unit' => 55000,
            'stock_qty_base_unit' => 20,
        ];

        // Store
        $response = $this->post('/products', $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Besi Beton 8mm',
            'photo_url' => null,
            'photo_public_id' => null,
        ]);

        $product = Product::where('name', 'Besi Beton 8mm')->first();

        // Update
        $updatePayload = [
            'name' => 'Besi Beton 8mm (Edit)',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'selling_price_per_base_unit' => 58000,
        ];

        $responseUpdate = $this->put("/products/{$product->id}", $updatePayload);
        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Besi Beton 8mm (Edit)',
            'photo_url' => null,
            'photo_public_id' => null,
        ]);
    }
}
