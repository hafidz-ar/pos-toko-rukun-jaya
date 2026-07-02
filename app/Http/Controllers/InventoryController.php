<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restock;
use App\Models\TransactionItem;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display inventory page with all products and stock info.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'baseUnit', 'units.unit'])
            ->active();

        // Search by name, category, or base_unit
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by category
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter: low stock only
        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        $products = $query->orderBy('name')->paginate($perPage)->withQueryString()->through(function ($product) {
            $altDisplay = $this->getAlternativeStockDisplay($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
                'base_unit' => $product->baseUnit->name,
                'base_unit_id' => $product->base_unit_id,
                'stock_qty_base_unit' => (float) $product->stock_qty_base_unit,
                'alt_stock_display' => $altDisplay,
                'cost_price_per_base_unit' => (float) $product->cost_price_per_base_unit,
                'selling_price_per_base_unit' => (float) $product->selling_price_per_base_unit,
                'location' => $product->location,
                'photo_url' => $product->photo_url,
                'min_stock_threshold' => $product->min_stock_threshold,
                'is_low_stock' => $product->stock_qty_base_unit <= $product->min_stock_threshold,
                'units' => $product->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_id' => $u->unit_id,
                    'unit_name' => $u->unit->name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'selling_price' => $u->selling_price !== null ? (float) $u->selling_price : (float) round($product->selling_price_per_base_unit * $u->conversion_factor),
                ]),
                'prediction' => $this->predictStockDepletion($product),
            ];
        });

        $categories = Category::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // Calculate active stats before pagination
        $stats = [
            'total_sku' => Product::active()->count(),
            'low_stock_count' => Product::active()->whereColumn('stock_qty_base_unit', '<=', 'min_stock_threshold')->count(),
            'total_valuation' => (float) Product::active()->selectRaw('SUM(stock_qty_base_unit * cost_price_per_base_unit) as total')->value('total'),
        ];

        // Check if it's an Inertia request or JSON (for polling)
        if ($request->wantsJson()) {
            return response()->json([
                'products' => $products,
                'stats' => $stats,
            ]);
        }

        return Inertia::render('Inventaris', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'stats' => $stats,
            'filters' => [
                'search' => $request->get('search', ''),
                'category_id' => $request->get('category_id', ''),
                'low_stock' => $request->boolean('low_stock'),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Get stock movement history for a product.
     */
    public function stockMovements(Product $product)
    {
        // Restock (in)
        $restocks = Restock::where('product_id', $product->id)
            ->with('user')
            ->orderByDesc('restocked_at')
            ->get()
            ->map(fn ($r) => [
                'type' => 'in',
                'qty' => $r->qty_base_unit,
                'unit_display' => $r->unit_name_at_restock,
                'hpp' => $r->cost_price_per_base_unit_at_restock,
                'location' => $r->location,
                'user' => $r->user->name,
                'datetime' => $r->restocked_at->format('d/m/Y H:i'),
                'timestamp' => $r->restocked_at->timestamp,
            ]);

        // Sales (out)
        $sales = TransactionItem::where('product_id', $product->id)
            ->with(['transaction.cashier'])
            ->get()
            ->map(fn ($ti) => [
                'type' => 'out',
                'qty' => $ti->qty_in_base_unit,
                'unit_display' => $ti->unit_name_at_transaction,
                'qty_display' => $ti->qty_in_selected_unit,
                'user' => $ti->transaction->cashier->name,
                'datetime' => $ti->transaction->transaction_datetime->format('d/m/Y H:i'),
                'timestamp' => $ti->transaction->transaction_datetime->timestamp,
            ]);

        // Merge and sort by timestamp descending
        $movements = $restocks->concat($sales)->sortByDesc('timestamp')->values();

        return response()->json($movements);
    }

    /**
     * Predict when stock will run out.
     */
    private function predictStockDepletion(Product $product): ?array
    {
        // Average daily sales velocity (last 30 days)
        $thirtyDaysAgo = now()->subDays(30);

        $totalSold = TransactionItem::where('product_id', $product->id)
            ->whereHas('transaction', fn ($q) => $q->where('transaction_datetime', '>=', $thirtyDaysAgo))
            ->get()
            ->sum(fn ($ti) => $ti->qty_in_base_unit);

        if ($totalSold <= 0) {
            return null; // No sales data
        }

        $daysOfData = max(1, now()->diffInDays($thirtyDaysAgo));
        $dailyVelocity = $totalSold / $daysOfData;

        if ($dailyVelocity <= 0) {
            return null;
        }

        $daysRemaining = $product->stock_qty_base_unit / $dailyVelocity;

        return [
            'daily_velocity' => round($dailyVelocity, 2),
            'days_remaining' => round($daysRemaining, 1),
            'estimated_date' => now()->addDays((int) $daysRemaining)->format('d/m/Y'),
        ];
    }

    /**
     * Format stock display with alternative units.
     */
    private function getAlternativeStockDisplay(Product $product): string
    {
        $stock = $product->stock_qty_base_unit;
        $parts = [number_format($stock, 0) . ' ' . $product->baseUnit->name];

        foreach ($product->units as $unit) {
            if ($unit->conversion_factor > 1) {
                $converted = $stock / $unit->conversion_factor;
                if ($converted >= 0.1) {
                    $parts[] = '≈ ' . number_format($converted, 1) . ' ' . $unit->unit->name;
                }
            }
        }

        return implode(' ', $parts);
    }
}
