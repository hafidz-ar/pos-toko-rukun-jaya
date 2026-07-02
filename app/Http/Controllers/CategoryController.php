<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories.
     */
    public function index()
    {
        $categories = Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'units' => 'nullable|string|max:255',
        ]);

        Category::create($validated);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'units' => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Delete a category (only if no active products).
     */
    public function destroy(Category $category)
    {
        $activeProducts = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->count();

        if ($activeProducts > 0) {
            return back()->with('error', "Tidak bisa menghapus kategori \"{$category->name}\" karena masih ada {$activeProducts} produk aktif.");
        }

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
