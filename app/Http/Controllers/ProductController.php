<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display product list (used by Inventaris page and Kasir search).
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'units'])
            ->active();

        // Search by name or base_unit
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by category
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();

        return response()->json($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_unit' => 'required|string|max:50',
            'cost_price_per_base_unit' => 'required|numeric|min:0',
            'selling_price_per_base_unit' => 'required|numeric|min:0',
            'stock_qty_base_unit' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'min_stock_threshold' => 'nullable|integer|min:0',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required_with:units|string|max:50',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_unit' => $validated['base_unit'],
            'cost_price_per_base_unit' => $validated['cost_price_per_base_unit'],
            'selling_price_per_base_unit' => $validated['selling_price_per_base_unit'],
            'stock_qty_base_unit' => $validated['stock_qty_base_unit'] ?? 0,
            'location' => $validated['location'] ?? null,
            'photo_url' => $validated['photo_url'] ?? null,
            'min_stock_threshold' => $validated['min_stock_threshold'] ?? 10,
        ]);

        // Create product units
        if (!empty($validated['units'])) {
            foreach ($validated['units'] as $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name' => $unit['unit_name'],
                    'conversion_factor' => $unit['conversion_factor'],
                ]);
            }
        }

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update the specified product.
     * Note: HPP (cost_price) is excluded — only changes via restock weighted average.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_unit' => 'required|string|max:50',
            'selling_price_per_base_unit' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'min_stock_threshold' => 'nullable|integer|min:0',
            'units' => 'nullable|array',
            'units.*.id' => 'nullable|integer',
            'units.*.unit_name' => 'required_with:units|string|max:50',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001',
        ]);

        // Update product (HPP excluded from manual edit per PRD 3.4)
        $product->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_unit' => $validated['base_unit'],
            'selling_price_per_base_unit' => $validated['selling_price_per_base_unit'],
            'location' => $validated['location'] ?? $product->location,
            'photo_url' => $validated['photo_url'] ?? $product->photo_url,
            'min_stock_threshold' => $validated['min_stock_threshold'] ?? $product->min_stock_threshold,
        ]);

        // Sync product units
        if (isset($validated['units'])) {
            // Delete removed units
            $keepIds = collect($validated['units'])->pluck('id')->filter()->toArray();
            $product->units()->whereNotIn('id', $keepIds)->delete();

            foreach ($validated['units'] as $unitData) {
                if (!empty($unitData['id'])) {
                    ProductUnit::where('id', $unitData['id'])->update([
                        'unit_name' => $unitData['unit_name'],
                        'conversion_factor' => $unitData['conversion_factor'],
                    ]);
                } else {
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_name' => $unitData['unit_name'],
                        'conversion_factor' => $unitData['conversion_factor'],
                    ]);
                }
            }
        }

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Soft delete (deactivate) the specified product.
     */
    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return back()->with('success', 'Produk berhasil dinonaktifkan.');
    }
}
