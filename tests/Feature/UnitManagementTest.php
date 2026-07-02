<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create owner user
        $this->owner = User::create([
            'name' => 'Test Owner',
            'username' => 'testowner',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Create category
        $this->category = Category::create([
            'name' => 'Bahan Bangunan',
        ]);
    }

    /**
     * Unused units can be deleted.
     */
    public function test_unused_unit_can_be_deleted(): void
    {
        $unit = Unit::create(['name' => 'kg', 'symbol' => 'kg']);

        $response = $this->actingAs($this->owner)
            ->delete("/units/{$unit->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    /**
     * Units used as products' base units cannot be deleted.
     */
    public function test_unit_used_as_base_unit_cannot_be_deleted(): void
    {
        $unit = Unit::create(['name' => 'kg', 'symbol' => 'kg']);

        Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unit->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/units/{$unit->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    /**
     * Units used as product alternative units cannot be deleted.
     */
    public function test_unit_used_as_alternative_unit_cannot_be_deleted(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 100,
            'is_active' => true,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt->id,
            'conversion_factor' => 50,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/units/{$unitAlt->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('units', ['id' => $unitAlt->id]);
    }

    /**
     * Unit names cannot be changed if they are in use.
     */
    public function test_unit_name_cannot_be_changed_if_used(): void
    {
        $unit = Unit::create(['name' => 'kg', 'symbol' => 'kg']);

        Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unit->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/units/{$unit->id}", [
                'name' => 'kilogram', // changed name
                'symbol' => 'kg-new', // changed symbol
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name']);
        
        $unit->refresh();
        $this->assertEquals('kg', $unit->name); // Name unchanged
    }

    /**
     * Symbol can be changed if unit is in use.
     */
    public function test_unit_symbol_can_be_changed_if_used(): void
    {
        $unit = Unit::create(['name' => 'kg', 'symbol' => 'kg']);

        Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unit->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/units/{$unit->id}", [
                'name' => 'kg', // name unchanged
                'symbol' => 'kg-new', // changed symbol
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        $unit->refresh();
        $this->assertEquals('kg-new', $unit->symbol); // Symbol updated
    }

    /**
     * Product validations block duplicate alternative units or base unit as alternative unit.
     */
    public function test_product_creation_validations(): void
    {
        $unit1 = Unit::create(['name' => 'pcs', 'symbol' => 'pcs']);
        $unit2 = Unit::create(['name' => 'box', 'symbol' => 'box']);

        // Case 1: Base unit same as alternative unit
        $response = $this->actingAs($this->owner)
            ->post('/products', [
                'name' => 'Paku',
                'category_id' => $this->category->id,
                'base_unit_id' => $unit1->id,
                'cost_price_per_base_unit' => 100,
                'selling_price_per_base_unit' => 150,
                'units' => [
                    ['unit_id' => $unit1->id, 'conversion_factor' => 10], // same as base_unit_id
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['units']);

        // Case 2: Duplicate alternative units
        $response = $this->actingAs($this->owner)
            ->post('/products', [
                'name' => 'Paku 2',
                'category_id' => $this->category->id,
                'base_unit_id' => $unit1->id,
                'cost_price_per_base_unit' => 100,
                'selling_price_per_base_unit' => 150,
                'units' => [
                    ['unit_id' => $unit2->id, 'conversion_factor' => 10],
                    ['unit_id' => $unit2->id, 'conversion_factor' => 12], // duplicate unit_id
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['units']);
    }

    /**
     * Selling price calculation uses custom price if provided, or rounded calculated price.
     */
    public function test_alternative_unit_custom_price_priority(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 100,
            'is_active' => true,
        ]);

        // Custom price provided
        $productUnitCustom = ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt->id,
            'conversion_factor' => 50,
            'selling_price' => 145000, // custom price is cheaper than 50 * 3000 (150000)
        ]);

        $unitAlt2 = Unit::create(['name' => 'box', 'symbol' => 'box']);

        // Calculated price (selling_price is null)
        $productUnitCalc = ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt2->id,
            'conversion_factor' => 10.5,
            'selling_price' => null,
        ]);

        // Simulate InventoryController response map structure
        $mappedCustomPrice = $productUnitCustom->selling_price !== null 
            ? (float) $productUnitCustom->selling_price 
            : (float) round($product->selling_price_per_base_unit * $productUnitCustom->conversion_factor);

        $mappedCalcPrice = $productUnitCalc->selling_price !== null 
            ? (float) $productUnitCalc->selling_price 
            : (float) round($product->selling_price_per_base_unit * $productUnitCalc->conversion_factor);

        $this->assertEquals(145000.00, $mappedCustomPrice);
        $this->assertEquals(31500.00, $mappedCalcPrice); // 3000 * 10.5 = 31500
    }

    /**
     * Sale of alternative unit reduces stock based on conversion factor.
     */
    public function test_alternative_unit_sale_reduces_base_stock(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 150.00, // 150 kg stock
            'is_active' => true,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt->id,
            'conversion_factor' => 50.00,
        ]);

        // Create transaction: buy 2 sak Semen Tiga Roda (which equals 100 kg)
        $response = $this->actingAs($this->owner)
            ->post('/kasir/store', [
                'payment_method' => 'tunai',
                'discount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'unit_name' => 'sak',
                        'conversion_factor' => 50.00,
                        'qty' => 2,
                        'price_per_unit' => 150000,
                    ]
                ]
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        
        $product->refresh();
        $this->assertEquals(50.00, $product->stock_qty_base_unit); // 150 - (2 * 50) = 50 kg
    }

    /**
     * Restocking using alternative units scales stock correctly.
     */
    public function test_alternative_unit_restock_increases_base_stock(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 50.00,
            'is_active' => true,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt->id,
            'conversion_factor' => 50.00,
        ]);

        // Create restock: add 3 sak Semen Tiga Roda (which equals 150 kg)
        $response = $this->actingAs($this->owner)
            ->post('/restock', [
                'product_id' => $product->id,
                'qty' => 3,
                'unit_name' => 'sak',
                'conversion_factor' => 50.00,
                'cost_price_per_base_unit' => 2100, // new cost price per kg
                'location' => 'Gudang Utama',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        $product->refresh();
        $this->assertEquals(200.00, $product->stock_qty_base_unit); // 50 + (3 * 50) = 200 kg
    }

    /**
     * Test selling price cannot be less than HPP on product creation.
     */
    public function test_selling_price_cannot_be_less_than_hpp_on_product_creation(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        // Case 1: Base unit selling price < cost price
        $response = $this->actingAs($this->owner)
            ->post('/products', [
                'name' => 'Semen',
                'category_id' => $this->category->id,
                'base_unit_id' => $unitBase->id,
                'cost_price_per_base_unit' => 2000,
                'selling_price_per_base_unit' => 1900, // < 2000
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['selling_price_per_base_unit']);

        // Case 2: Alt unit custom selling price < unit HPP
        $response = $this->actingAs($this->owner)
            ->post('/products', [
                'name' => 'Semen 2',
                'category_id' => $this->category->id,
                'base_unit_id' => $unitBase->id,
                'cost_price_per_base_unit' => 2000,
                'selling_price_per_base_unit' => 3000,
                'units' => [
                    [
                        'unit_id' => $unitAlt->id,
                        'conversion_factor' => 10,
                        'selling_price' => 19000, // 19000 < 2000 * 10 (20000)
                    ]
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['units']);
    }

    /**
     * Test selling price cannot be less than HPP on product update.
     */
    public function test_selling_price_cannot_be_less_than_hpp_on_product_update(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 10,
            'is_active' => true,
        ]);

        // Case 1: Base unit selling price < cost price
        $response = $this->actingAs($this->owner)
            ->put("/products/{$product->id}", [
                'name' => 'Semen Tiga Roda',
                'category_id' => $this->category->id,
                'base_unit_id' => $unitBase->id,
                'selling_price_per_base_unit' => 1900, // < 2000
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['selling_price_per_base_unit']);

        // Case 2: Alt unit custom selling price < unit HPP
        $response = $this->actingAs($this->owner)
            ->put("/products/{$product->id}", [
                'name' => 'Semen Tiga Roda',
                'category_id' => $this->category->id,
                'base_unit_id' => $unitBase->id,
                'selling_price_per_base_unit' => 3000,
                'units' => [
                    [
                        'unit_id' => $unitAlt->id,
                        'conversion_factor' => 10,
                        'selling_price' => 19000, // < 2000 * 10
                    ]
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['units']);
    }

    /**
     * Test restock fails if new weighted HPP exceeds selling price.
     */
    public function test_restock_fails_if_new_weighted_hpp_exceeds_selling_price(): void
    {
        $unitBase = Unit::create(['name' => 'kg', 'symbol' => 'kg']);
        $unitAlt = Unit::create(['name' => 'sak', 'symbol' => 'sak']);

        $product = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $unitBase->id,
            'cost_price_per_base_unit' => 2000,
            'selling_price_per_base_unit' => 3000,
            'stock_qty_base_unit' => 10.00,
            'is_active' => true,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'unit_id' => $unitAlt->id,
            'conversion_factor' => 10.00,
            'selling_price' => 25000, // selling price is 25000 for 10 kg
        ]);

        // Restock with very high cost price (e.g. 5000 per kg) which will push weighted HPP to:
        // (10 * 2000 + 10 * 5000) / 20 = 3500 per kg
        // 3500 > 3000 (base unit selling price) and 35000 > 25000 (alt unit selling price)
        $response = $this->actingAs($this->owner)
            ->post('/restock', [
                'product_id' => $product->id,
                'qty' => 1,
                'unit_name' => 'sak',
                'conversion_factor' => 10.00,
                'cost_price_per_base_unit' => 5000,
                'location' => 'Gudang Utama',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
