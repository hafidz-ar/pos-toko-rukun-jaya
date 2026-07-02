<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Store a newly created master unit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
            'symbol' => 'nullable|string|max:10',
        ]);

        // Normalize name to lower case
        $validated['name'] = trim(strtolower($validated['name']));

        Unit::create($validated);

        return back()->with('success', 'Satuan berhasil ditambahkan.');
    }

    /**
     * Update the specified master unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name,' . $unit->id,
            'symbol' => 'nullable|string|max:10',
        ]);

        $normalizedName = trim(strtolower($validated['name']));

        // Check if unit is in use by products or product units
        $inUse = Product::where('base_unit_id', $unit->id)->exists() ||
                 ProductUnit::where('unit_id', $unit->id)->exists();

        if ($inUse && $normalizedName !== $unit->name) {
            return back()->withErrors([
                'name' => 'Nama satuan tidak dapat diubah karena sedang digunakan oleh produk. Anda hanya diperkenankan mengubah simbol.'
            ]);
        }

        $unit->update([
            'name' => $normalizedName,
            'symbol' => $validated['symbol'],
        ]);

        return back()->with('success', 'Satuan berhasil diperbarui.');
    }

    /**
     * Remove the specified master unit from storage.
     */
    public function destroy(Unit $unit)
    {
        // Check database constraint programmatically for user friendly message
        $inUseInProducts = Product::where('base_unit_id', $unit->id)->exists();
        $inUseInProductUnits = ProductUnit::where('unit_id', $unit->id)->exists();

        if ($inUseInProducts || $inUseInProductUnits) {
            $count = Product::where('base_unit_id', $unit->id)->count() + ProductUnit::where('unit_id', $unit->id)->count();
            return back()->withErrors([
                'error' => "Gagal menghapus satuan \"{$unit->name}\" karena masih digunakan oleh {$count} produk atau konversi produk."
            ]);
        }

        $unit->delete();

        return back()->with('success', 'Satuan berhasil dihapus.');
    }
}
