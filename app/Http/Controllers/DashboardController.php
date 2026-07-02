<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Dashboard is Owner-only (PRD 3.7 — contains financial data)
        if ($request->user()->isKaryawan()) {
            return redirect()->route('kasir');
        }

        $period = $request->get('period', 'minggu'); // harian, minggu, bulan
        $now = Carbon::now();

        // Determine date range based on period
        switch ($period) {
            case 'harian':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'bulan':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'minggu':
            default:
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
                break;
        }

        // 1. Total Omset (periode berjalan)
        $totalOmset = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])
            ->sum('total_amount');

        // 2. Jumlah Transaksi (periode berjalan)
        $jumlahTransaksi = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])
            ->count();

        // 3. Laba Kotor
        $transactionItems = TransactionItem::whereHas('transaction', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('transaction_datetime', [$startDate, $endDate]);
        })->with('product.category')->get();

        $totalLabaKotor = $transactionItems->sum(function ($item) {
            return $item->profit;
        });

        // 3a. Breakdown per produk
        $labaPerProduk = $transactionItems->groupBy('product_id')->map(function ($items) {
            $first = $items->first();
            return [
                'product_id' => $first->product_id,
                'product_name' => $first->product?->name ?? 'Produk Dihapus',
                'total_revenue' => $items->sum(fn ($i) => $i->subtotal),
                'total_profit' => $items->sum(fn ($i) => $i->profit),
                'total_qty' => $items->sum(fn ($i) => $i->qty_in_base_unit),
            ];
        })->sortByDesc('total_profit')->values()->take(10);

        // 3b. Breakdown per kategori
        $labaPerKategori = $transactionItems->groupBy(fn ($item) => $item->product?->category_id)->map(function ($items) {
            $first = $items->first();
            return [
                'category_name' => $first->product?->category?->name ?? 'Tanpa Kategori',
                'total_revenue' => $items->sum(fn ($i) => $i->subtotal),
                'total_profit' => $items->sum(fn ($i) => $i->profit),
            ];
        })->sortByDesc('total_profit')->values();

        // 4. Stok Kritis
        $stokKritis = Product::active()->lowStock()
            ->with('category')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'stock' => $p->stock_qty_base_unit,
                'base_unit' => $p->base_unit,
                'threshold' => $p->min_stock_threshold,
            ]);

        // 5. Indikator Rugi (transaksi merugi — should be 0)
        $transaksiMerugi = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])
            ->whereRaw('total_amount < (SELECT COALESCE(SUM(ti.cost_price_per_base_unit_at_transaction * ti.qty_in_selected_unit * ti.conversion_factor_at_transaction), 0) FROM transaction_items ti WHERE ti.transaction_id = transactions.id)')
            ->count();

        // 6. Grafik penjualan rolling 7 hari terakhir (paling kanan adalah hari ini)
        $chartData = [];
        
        $indonesianDays = [
            'Sunday' => 'Min',
            'Monday' => 'Sen',
            'Tuesday' => 'Sel',
            'Wednesday' => 'Rab',
            'Thursday' => 'Kam',
            'Friday' => 'Jum',
            'Saturday' => 'Sab'
        ];

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dayOmset = Transaction::whereDate('transaction_datetime', $day->toDateString())
                ->sum('total_amount');

            $dayName = $indonesianDays[$day->format('l')] ?? substr($day->format('D'), 0, 3);

            $chartData[] = [
                'day' => $dayName,
                'date' => $day->format('d/m'),
                'amount' => (float) $dayOmset,
            ];
        }

        // 7. Riwayat transaksi hari ini
        $riwayatHariIni = Transaction::whereDate('transaction_datetime', $now->toDateString())
            ->with(['cashier', 'items.product'])
            ->orderByDesc('transaction_datetime')
            ->limit(10)
            ->get()
            ->map(fn ($txn) => [
                'id' => $txn->id,
                'waktu' => $txn->transaction_datetime->format('H:i') . ' WIB',
                'cashier' => $txn->cashier->name,
                'items_summary' => $txn->items->map(fn ($i) => $i->product?->name)->implode(', '),
                'items_count' => $txn->items->count(),
                'total' => $txn->total_amount,
                'payment_method' => $txn->payment_method,
                'discount' => $txn->discount_amount,
            ]);

        return Inertia::render('Dashboard', [
            'period' => $period,
            'totalOmset' => (float) $totalOmset,
            'jumlahTransaksi' => $jumlahTransaksi,
            'totalLabaKotor' => (float) $totalLabaKotor,
            'labaPerProduk' => $labaPerProduk,
            'labaPerKategori' => $labaPerKategori,
            'stokKritis' => $stokKritis,
            'transaksiMerugi' => $transaksiMerugi,
            'chartData' => $chartData,
            'riwayatHariIni' => $riwayatHariIni,
        ]);
    }
}
