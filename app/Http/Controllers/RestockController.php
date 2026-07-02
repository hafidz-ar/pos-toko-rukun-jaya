<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Restock;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RestockController extends Controller
{
    /**
     * Display restock page with products and restock history.
     */
    public function index(Request $request)
    {
        $query = Restock::with(['product.category', 'user']);

        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        $restocks = $query->orderByDesc('restocked_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($r) => [
                'id' => $r->id,
                'product_name' => $r->product->name,
                'product_category' => $r->product->category->name ?? '-',
                'qty_base_unit' => (float) $r->qty_base_unit,
                'base_unit' => $r->product->baseUnit->name ?? '-',
                'unit_name' => $r->unit_name_at_restock,
                'hpp' => (float) $r->cost_price_per_base_unit_at_restock,
                'location' => $r->location,
                'user' => $r->user->name,
                'datetime' => $r->restocked_at->format('d/m/Y H:i'),
            ]);

        // Products with units for the restock form
        $products = Product::with(['category', 'baseUnit', 'units.unit'])
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'base_unit' => $p->baseUnit->name,
                'cost_price_per_base_unit' => (float) $p->cost_price_per_base_unit,
                'stock_qty_base_unit' => (float) $p->stock_qty_base_unit,
                'location' => $p->location,
                'units' => $p->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_name' => $u->unit->name,
                    'conversion_factor' => (float) $u->conversion_factor,
                ]),
            ]);

        // JSON response for AJAX/polling
        if ($request->wantsJson()) {
            return response()->json([
                'restocks' => $restocks,
                'products' => $products,
            ]);
        }

        return Inertia::render('Restock', [
            'restocks' => $restocks,
            'products' => $products,
            'filters' => [
                'product_id' => $request->get('product_id', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Validate restock data before submission (soft-block check).
     */
    public function validate_restock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'hpp' => 'required|numeric|min:0',
            'qty_base_unit' => 'required|numeric|min:0.01',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $warnings = [];

        // HPP anomaly check (>20% difference from current)
        if ($product->cost_price_per_base_unit > 0) {
            $hppDiff = abs($validated['hpp'] - $product->cost_price_per_base_unit) / $product->cost_price_per_base_unit;
            if ($hppDiff > 0.20) {
                $warnings[] = [
                    'type' => 'hpp',
                    'message' => 'HPP yang Anda input (Rp ' . number_format($validated['hpp'], 0, ',', '.') .
                        ') berbeda >' . round($hppDiff * 100) . '% dari HPP saat ini (Rp ' .
                        number_format($product->cost_price_per_base_unit, 0, ',', '.') . '). Lanjutkan?',
                ];
            }
        }

        // Qty anomaly check (>20% from average of last 5 restocks)
        $avgQty = Restock::where('product_id', $product->id)
            ->orderByDesc('restocked_at')
            ->limit(5)
            ->avg('qty_base_unit');

        if ($avgQty > 0) {
            $qtyDiff = abs($validated['qty_base_unit'] - $avgQty) / $avgQty;
            if ($qtyDiff > 0.20) {
                $warnings[] = [
                    'type' => 'qty',
                    'message' => 'Qty restock ini (' . number_format($validated['qty_base_unit'], 0) .
                        ') jauh berbeda dari kebiasaan restock produk ini (rata-rata ' .
                        number_format($avgQty, 0) . '). Lanjutkan?',
                ];
            }
        }

        return response()->json(['warnings' => $warnings]);
    }

    /**
     * Process a restock.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_name' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'cost_price_per_base_unit' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

                $qtyBaseUnit = $validated['qty'] * $validated['conversion_factor'];
                $newHPP = $validated['cost_price_per_base_unit'];

                // Calculate weighted average HPP
                $oldStock = $product->stock_qty_base_unit;
                $oldHPP = $product->cost_price_per_base_unit;

                if ($oldStock + $qtyBaseUnit > 0) {
                    $weightedHPP = ($oldStock * $oldHPP + $qtyBaseUnit * $newHPP) / ($oldStock + $qtyBaseUnit);
                } else {
                    $weightedHPP = $newHPP;
                }

                $roundedWeightedHPP = round($weightedHPP, 2);

                // Check if the new HPP causes base unit price to fall below HPP
                if ($product->selling_price_per_base_unit < $roundedWeightedHPP) {
                    throw new \Exception(
                        "Restock gagal: HPP rata-rata baru (Rp " . number_format($roundedWeightedHPP, 0, ',', '.') . ") " .
                        "melebihi harga jual dasar (Rp " . number_format($product->selling_price_per_base_unit, 0, ',', '.') . "). " .
                        "Naikkan harga jual produk terlebih dahulu."
                    );
                }

                // Check if the new HPP causes any alternative unit price to fall below HPP
                foreach ($product->units as $pu) {
                    $unitSellingPrice = $pu->selling_price !== null 
                        ? (float) $pu->selling_price 
                        : (float) round($product->selling_price_per_base_unit * $pu->conversion_factor);
                    $unitHPP = (float) round($roundedWeightedHPP * $pu->conversion_factor, 2);
                    
                    if ($unitSellingPrice < $unitHPP) {
                        throw new \Exception(
                            "Restock gagal: HPP rata-rata baru mengakibatkan harga jual satuan alternatif '{$pu->unit->name}' " .
                            "(Rp " . number_format($unitSellingPrice, 0, ',', '.') . ") kurang dari HPP-nya " .
                            "(Rp " . number_format($unitHPP, 0, ',', '.') . "). " .
                            "Naikkan harga jual produk/satuan alternatif terlebih dahulu."
                        );
                    }
                }

                // Create restock record
                $restock = Restock::create([
                    'product_id' => $product->id,
                    'qty_base_unit' => $qtyBaseUnit,
                    'unit_name_at_restock' => $validated['unit_name'],
                    'cost_price_per_base_unit_at_restock' => $newHPP,
                    'location' => $validated['location'],
                    'restocked_by_user_id' => auth()->id(),
                    'restocked_at' => now(),
                ]);

                // Update product
                $product->update([
                    'stock_qty_base_unit' => $oldStock + $qtyBaseUnit,
                    'cost_price_per_base_unit' => $roundedWeightedHPP,
                    'location' => $validated['location'],
                ]);

                // Check anomaly
                $isAnomaly = false;
                if ($oldHPP > 0 && abs($newHPP - $oldHPP) / $oldHPP > 0.20) {
                    $isAnomaly = true;
                }
                $avgQty = Restock::where('product_id', $product->id)
                    ->where('id', '!=', $restock->id)
                    ->orderByDesc('restocked_at')
                    ->limit(5)
                    ->avg('qty_base_unit');
                if ($avgQty > 0 && abs($qtyBaseUnit - $avgQty) / $avgQty > 0.20) {
                    $isAnomaly = true;
                }

                // Build notification message
                $prefix = $isAnomaly ? '⚠️ Restock perlu dicek:' : 'Restock:';
                $qtyDisplay = number_format($validated['qty'], 0) . ' ' . $validated['unit_name'];
                $qtyBaseDisplay = number_format($qtyBaseUnit, 0) . ' ' . $product->baseUnit->name;
                $oldHppFormatted = $oldHPP > 0 ? 'Rp ' . number_format($oldHPP, 0, ',', '.') : '-';
                $message = $prefix . "\n" .
                    "- " . $product->name . ' — ' . $qtyDisplay . ' (' . $qtyBaseDisplay . ') oleh ' . auth()->user()->name . ".\n" .
                    'HPP Baru: Rp ' . number_format($newHPP, 0, ',', '.') . '/' . $product->baseUnit->name .
                    ' (HPP Lama: ' . $oldHppFormatted . '/' . $product->baseUnit->name . ').';

                // Send notification to all owners
                $owners = User::where('role', 'owner')->where('is_active', true)->get();
                foreach ($owners as $owner) {
                    Notification::create([
                        'recipient_user_id' => $owner->id,
                        'type' => 'restock',
                        'related_restock_id' => $restock->id,
                        'is_anomaly' => $isAnomaly,
                        'message' => $message,
                        'is_read' => false,
                        'created_at' => now(),
                    ]);

                    // Send Telegram notification (fail-silent)
                    if ($owner->telegram_chat_id) {
                        try {
                            app(TelegramService::class)->sendMessage($owner->telegram_chat_id, $message);
                        } catch (\Throwable $e) {
                            // Fail-silent per PRD — in-app is source of truth
                            \Log::warning('Telegram notification failed: ' . $e->getMessage());
                        }
                    }
                }

                return back()->with('success', 'Restock berhasil disimpan. Stok dan HPP telah diperbarui.');
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
