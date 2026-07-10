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
            'name' => 'required|string|max:255|unique:products,name,NULL,id,is_active,1',
            'category_id' => 'required|exists:categories,id',
            'base_unit_id' => 'required|exists:units,id',
            'cost_price_per_base_unit' => 'required|numeric|min:0',
            'selling_price_per_base_unit' => 'required|numeric|min:0',
            'stock_qty_base_unit' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'photo_file' => 'nullable|image|mimes:jpeg,png,webp|max:5120', // Max 5MB
            'min_stock_threshold' => 'nullable|integer|min:0',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'required_with:units|exists:units,id',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001|max:1000000',
            'units.*.selling_price' => 'nullable|numeric|min:0',
        ], [
            'name.unique' => 'Nama barang sudah terdaftar di sistem.',
        ]);

        // Business validations
        if ($validated['selling_price_per_base_unit'] < $validated['cost_price_per_base_unit']) {
            return back()->withErrors(['selling_price_per_base_unit' => 'Harga jual tidak boleh kurang dari harga HPP.']);
        }

        if (!empty($validated['units'])) {
            $unitIds = [];
            foreach ($validated['units'] as $unit) {
                if ($validated['base_unit_id'] == $unit['unit_id']) {
                    return back()->withErrors(['units' => 'Satuan dasar tidak boleh sama dengan satuan alternatif.']);
                }

                if (isset($unit['selling_price']) && $unit['selling_price'] !== null && $unit['selling_price'] !== '') {
                    $unitHpp = $unit['conversion_factor'] * $validated['cost_price_per_base_unit'];
                    if ($unit['selling_price'] < $unitHpp) {
                        $unitModel = \App\Models\Unit::find($unit['unit_id']);
                        $unitName = $unitModel ? $unitModel->name : 'alternatif';
                        return back()->withErrors(['units' => "Harga jual satuan alternatif '{$unitName}' tidak boleh kurang dari harga HPP (Rp " . number_format($unitHpp, 0, ',', '.') . ")."]);
                    }
                }
                $unitIds[] = $unit['unit_id'];
            }
            if (count($unitIds) !== count(array_unique($unitIds))) {
                return back()->withErrors(['units' => 'Satuan alternatif tidak boleh duplikat.']);
            }
        }

        $photoUrl = $validated['photo_url'] ?? null;
        $photoPublicId = null;

        if ($request->hasFile('photo_file')) {
            try {
                $uploadResult = app(\App\Services\CloudinaryService::class)->upload($request->file('photo_file'));
                $photoUrl = $uploadResult['secure_url'];
                $photoPublicId = $uploadResult['public_id'];
            } catch (\Exception $e) {
                return back()->withErrors(['photo_file' => $e->getMessage()]);
            }
        }

        $product = Product::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_unit_id' => $validated['base_unit_id'],
            'cost_price_per_base_unit' => $validated['cost_price_per_base_unit'],
            'selling_price_per_base_unit' => $validated['selling_price_per_base_unit'],
            'stock_qty_base_unit' => $validated['stock_qty_base_unit'] ?? 0,
            'location' => $validated['location'] ?? null,
            'photo_url' => $photoUrl,
            'photo_public_id' => $photoPublicId,
            'min_stock_threshold' => $validated['min_stock_threshold'] ?? 10,
        ]);

        // Create product units
        if (!empty($validated['units'])) {
            foreach ($validated['units'] as $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unit['unit_id'],
                    'conversion_factor' => $unit['conversion_factor'],
                    'selling_price' => $unit['selling_price'] ?? null,
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
            'name' => 'required|string|max:255|unique:products,name,' . $product->id . ',id,is_active,1',
            'category_id' => 'required|exists:categories,id',
            'base_unit_id' => 'required|exists:units,id',
            'selling_price_per_base_unit' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'photo_file' => 'nullable|image|mimes:jpeg,png,webp|max:5120', // Max 5MB
            'min_stock_threshold' => 'nullable|integer|min:0',
            'units' => 'nullable|array',
            'units.*.id' => 'nullable|integer',
            'units.*.unit_id' => 'required_with:units|exists:units,id',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001|max:1000000',
            'units.*.selling_price' => 'nullable|numeric|min:0',
        ], [
            'name.unique' => 'Nama barang sudah terdaftar di sistem.',
        ]);

        // Business validations
        if ($validated['selling_price_per_base_unit'] < $product->cost_price_per_base_unit) {
            return back()->withErrors(['selling_price_per_base_unit' => 'Harga jual tidak boleh kurang dari harga HPP.']);
        }

        if (!empty($validated['units'])) {
            $unitIds = [];
            foreach ($validated['units'] as $unit) {
                if ($validated['base_unit_id'] == $unit['unit_id']) {
                    return back()->withErrors(['units' => 'Satuan dasar tidak boleh sama dengan satuan alternatif.']);
                }

                if (isset($unit['selling_price']) && $unit['selling_price'] !== null && $unit['selling_price'] !== '') {
                    $unitHpp = $unit['conversion_factor'] * $product->cost_price_per_base_unit;
                    if ($unit['selling_price'] < $unitHpp) {
                        $unitModel = \App\Models\Unit::find($unit['unit_id']);
                        $unitName = $unitModel ? $unitModel->name : 'alternatif';
                        return back()->withErrors(['units' => "Harga jual satuan alternatif '{$unitName}' tidak boleh kurang dari harga HPP (Rp " . number_format($unitHpp, 0, ',', '.') . ")."]);
                    }
                }
                $unitIds[] = $unit['unit_id'];
            }
            if (count($unitIds) !== count(array_unique($unitIds))) {
                return back()->withErrors(['units' => 'Satuan alternatif tidak boleh duplikat.']);
            }
        }

        $photoUrl = $product->photo_url;
        $photoPublicId = $product->photo_public_id;

        if ($request->hasFile('photo_file')) {
            try {
                $uploadResult = app(\App\Services\CloudinaryService::class)->upload($request->file('photo_file'), $product->photo_public_id);
                $photoUrl = $uploadResult['secure_url'];
                $photoPublicId = $uploadResult['public_id'];
            } catch (\Exception $e) {
                return back()->withErrors(['photo_file' => $e->getMessage()]);
            }
        } elseif (array_key_exists('photo_url', $validated)) {
            if ($validated['photo_url'] !== $product->photo_url && $product->photo_public_id) {
                app(\App\Services\CloudinaryService::class)->delete($product->photo_public_id);
                $photoPublicId = null;
            }
            $photoUrl = $validated['photo_url'];
        }

        // Update product (HPP excluded from manual edit per PRD 3.4)
        $product->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_unit_id' => $validated['base_unit_id'],
            'selling_price_per_base_unit' => $validated['selling_price_per_base_unit'],
            'location' => $validated['location'] ?? $product->location,
            'photo_url' => $photoUrl,
            'photo_public_id' => $photoPublicId,
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
                        'unit_id' => $unitData['unit_id'],
                        'conversion_factor' => $unitData['conversion_factor'],
                        'selling_price' => $unitData['selling_price'] ?? null,
                    ]);
                } else {
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_id' => $unitData['unit_id'],
                        'conversion_factor' => $unitData['conversion_factor'],
                        'selling_price' => $unitData['selling_price'] ?? null,
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
        // Delete photo from Cloudinary if it exists
        if ($product->photo_public_id) {
            try {
                app(\App\Services\CloudinaryService::class)->delete($product->photo_public_id);
            } catch (\Exception $e) {
                // Silent fail — log only, do not block deactivation
                \Log::warning("Failed to delete Cloudinary photo on product deactivation: " . $e->getMessage());
            }
        }

        $product->update([
            'is_active' => false,
            'photo_url' => null,
            'photo_public_id' => null,
        ]);

        return back()->with('success', 'Produk berhasil dinonaktifkan.');
    }
}
