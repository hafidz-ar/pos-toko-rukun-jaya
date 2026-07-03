<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndSalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    private $owner;
    private $karyawan1;
    private $karyawan2;
    private $category;
    private $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles
        $this->owner = User::create([
            'name' => 'Owner Ali',
            'username' => 'owner_ali',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $this->karyawan1 = User::create([
            'name' => 'Karyawan Budi',
            'username' => 'karyawan_budi',
            'email' => 'budi@test.com',
            'password' => bcrypt('password'),
            'role' => 'karyawan',
        ]);

        $this->karyawan2 = User::create([
            'name' => 'Karyawan Cici',
            'username' => 'karyawan_cici',
            'email' => 'cici@test.com',
            'password' => bcrypt('password'),
            'role' => 'karyawan',
        ]);

        // Create category and unit for products
        $this->category = Category::create(['name' => 'Bahan Bangunan']);
        $this->unit = Unit::create(['name' => 'sak']);
    }

    /**
     * Test Dashboard scoping based on user roles.
     */
    public function test_dashboard_scope_metrics_by_role()
    {
        // Create transactions processed by different cashiers
        Transaction::create([
            'cashier_user_id' => $this->karyawan1->id,
            'transaction_datetime' => Carbon::now('Asia/Jakarta'),
            'total_amount' => 500000,
            'subtotal_before_discount' => 500000,
            'discount_amount' => 0,
            'cash_received' => 500000,
            'payment_method' => 'tunai',
        ]);

        Transaction::create([
            'cashier_user_id' => $this->karyawan2->id,
            'transaction_datetime' => Carbon::now('Asia/Jakarta'),
            'total_amount' => 1500000,
            'subtotal_before_discount' => 1500000,
            'discount_amount' => 0,
            'cash_received' => 0,
            'payment_method' => 'qris',
        ]);

        // 1. Owner should see all transactions metrics
        $this->actingAs($this->owner);
        $responseOwner = $this->get('/dashboard');
        $responseOwner->assertStatus(200);
        
        $ownerProps = $responseOwner->original->getData()['page']['props'];
        $this->assertEquals(2, $ownerProps['todayCount']);
        $this->assertEquals(2000000, $ownerProps['todayOmset']);

        // 2. Karyawan 1 should only see their own metrics
        $this->actingAs($this->karyawan1);
        $responseKaryawan = $this->get('/dashboard');
        $responseKaryawan->assertStatus(200);

        $karyawanProps = $responseKaryawan->original->getData()['page']['props'];
        $this->assertEquals(1, $karyawanProps['todayCount']);
        $this->assertEquals(500000, $karyawanProps['todayOmset']);

        // 3. Karyawan 1 tries to manipulate URL using cashier parameter, metrics should still be scoped to their own
        $responseManipulated = $this->get('/dashboard?cashier_id=' . $this->karyawan2->id);
        $responseManipulatedProps = $responseManipulated->original->getData()['page']['props'];
        $this->assertEquals(1, $responseManipulatedProps['todayCount']);
        $this->assertEquals(500000, $responseManipulatedProps['todayOmset']);
    }

    /**
     * Test transaction detail view authorization.
     */
    public function test_transaction_detail_authorization_rules()
    {
        // Create transaction processed by Karyawan 1
        $txn = Transaction::create([
            'cashier_user_id' => $this->karyawan1->id,
            'transaction_datetime' => Carbon::now('Asia/Jakarta'),
            'total_amount' => 350000,
            'subtotal_before_discount' => 350000,
            'discount_amount' => 0,
            'cash_received' => 350000,
            'payment_method' => 'tunai',
        ]);

        // 1. Owner can view it
        $this->actingAs($this->owner)
            ->getJson("/penjualan/{$txn->id}")
            ->assertStatus(200);

        // 2. Karyawan 1 can view it (processed by themselves)
        $this->actingAs($this->karyawan1)
            ->getJson("/penjualan/{$txn->id}")
            ->assertStatus(200);

        // 3. Karyawan 2 cannot view it (processed by other cashier)
        $this->actingAs($this->karyawan2)
            ->getJson("/penjualan/{$txn->id}")
            ->assertStatus(403);
    }

    /**
     * Test critical stock rules, prioritization of out-of-stock items, and counters.
     */
    public function test_critical_stock_rules_and_counters()
    {
        // A. Product out of stock (active, threshold 10, stock 0) - Out of stock
        $prodA = Product::create([
            'name' => 'Semen Tiga Roda',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 50000,
            'selling_price_per_base_unit' => 60000,
            'stock_qty_base_unit' => 0,
            'min_stock_threshold' => 10,
            'is_active' => true,
        ]);

        // B. Product low stock (active, threshold 10, stock 2) - Low stock
        $prodB = Product::create([
            'name' => 'Besi Beton 8mm',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 45000,
            'selling_price_per_base_unit' => 55000,
            'stock_qty_base_unit' => 2,
            'min_stock_threshold' => 10,
            'is_active' => true,
        ]);

        // C. Product low stock (active, threshold 10, stock 8) - Low stock
        $prodC = Product::create([
            'name' => 'Paku 5cm',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 12000,
            'selling_price_per_base_unit' => 15000,
            'stock_qty_base_unit' => 8,
            'min_stock_threshold' => 10,
            'is_active' => true,
        ]);

        // D. Product safe stock (active, threshold 10, stock 20) - Safe
        $prodD = Product::create([
            'name' => 'Pasir Pasang',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 200000,
            'selling_price_per_base_unit' => 250000,
            'stock_qty_base_unit' => 20,
            'min_stock_threshold' => 10,
            'is_active' => true,
        ]);

        // E. Inactive product (should be ignored entirely)
        $prodE = Product::create([
            'name' => 'Kayu Usuk (Nonaktif)',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 10000,
            'selling_price_per_base_unit' => 14000,
            'stock_qty_base_unit' => 1,
            'min_stock_threshold' => 10,
            'is_active' => false,
        ]);

        // F. Product with minimum stock threshold = 0 (should be ignored entirely)
        $prodF = Product::create([
            'name' => 'Batu Belah',
            'category_id' => $this->category->id,
            'base_unit_id' => $this->unit->id,
            'cost_price_per_base_unit' => 150000,
            'selling_price_per_base_unit' => 180000,
            'stock_qty_base_unit' => 0,
            'min_stock_threshold' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner);
        $response = $this->get('/dashboard');
        $props = $response->original->getData()['page']['props'];

        // Counters check
        $this->assertEquals(1, $props['outOfStockCount']); // Product A
        $this->assertEquals(2, $props['lowStockCount']); // Product B and C
        $this->assertEquals(3, $props['criticalStockCount']); // Product A, B, C

        // List ordering check:
        // Product A (stock 0) must appear first (Index 0).
        // Product B (stock 2, ratio 2/10 = 0.2) must appear second (Index 1).
        // Product C (stock 8, ratio 8/10 = 0.8) must appear third (Index 2).
        $products = $props['criticalProducts'];
        $this->assertCount(3, $products);
        
        $this->assertEquals('Semen Tiga Roda', $products[0]['name']);
        $this->assertEquals('Besi Beton 8mm', $products[1]['name']);
        $this->assertEquals('Paku 5cm', $products[2]['name']);
    }
}
