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
        $user = $request->user();
        $isKaryawan = $user->isKaryawan();
        $period = $request->get('period', 'minggu'); // harian, minggu, bulan
        $now = Carbon::now('Asia/Jakarta');

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

        // A. Performa Hari Ini (Kasir / Jualan Card) - Scoped by Role
        $todayQuery = Transaction::whereDate('transaction_datetime', Carbon::today('Asia/Jakarta'));
        if ($isKaryawan) {
            $todayQuery->where('cashier_user_id', $user->id);
        }
        $todayCount = $todayQuery->count();
        $todayOmset = (float) $todayQuery->sum('total_amount');

        // B. Ringkasan Statistik Mingguan - Scoped by Role
        // 1. Total Omset (periode berjalan)
        $totalOmsetQuery = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate]);
        if ($isKaryawan) {
            $totalOmsetQuery->where('cashier_user_id', $user->id);
        }
        $totalOmset = (float) $totalOmsetQuery->sum('total_amount');

        // 2. Jumlah Transaksi (periode berjalan)
        $jumlahTransaksiQuery = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate]);
        if ($isKaryawan) {
            $jumlahTransaksiQuery->where('cashier_user_id', $user->id);
        }
        $jumlahTransaksi = $jumlahTransaksiQuery->count();

        // 3. Laba Kotor (periode berjalan)
        $transactionItemsQuery = TransactionItem::whereHas('transaction', function ($q) use ($startDate, $endDate, $isKaryawan, $user) {
            $q->whereBetween('transaction_datetime', [$startDate, $endDate]);
            if ($isKaryawan) {
                $q->where('cashier_user_id', $user->id);
            }
        })->with('product.category');

        $transactionItems = $transactionItemsQuery->get();
        $totalLabaKotor = (float) $transactionItems->sum(fn ($item) => $item->profit);

        $labaPerProduk = collect();
        $labaPerKategori = collect();
        if (!$isKaryawan) {
            // Breakdown per produk
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

            // Breakdown per kategori
            $labaPerKategori = $transactionItems->groupBy(fn ($item) => $item->product?->category_id)->map(function ($items) {
                $first = $items->first();
                return [
                    'category_name' => $first->product?->category?->name ?? 'Tanpa Kategori',
                    'total_revenue' => $items->sum(fn ($i) => $i->subtotal),
                    'total_profit' => $items->sum(fn ($i) => $i->profit),
                ];
            })->sortByDesc('total_profit')->values();
        }

        // C. Stok Kritis & Rincian Cek Stok
        $criticalStockBaseQuery = Product::active()->lowStock()
            ->where('min_stock_threshold', '>', 0);

        $criticalStockCount = (clone $criticalStockBaseQuery)->count();
        $lowStockCount = (clone $criticalStockBaseQuery)->where('stock_qty_base_unit', '>', 0)->count();
        $outOfStockCount = (clone $criticalStockBaseQuery)->where('stock_qty_base_unit', '<=', 0)->count();

        $criticalProducts = (clone $criticalStockBaseQuery)
            ->with(['category', 'baseUnit'])
            ->orderByRaw('CASE WHEN stock_qty_base_unit <= 0 THEN 0 ELSE 1 END ASC')
            ->orderByRaw('(stock_qty_base_unit / min_stock_threshold) ASC')
            ->orderBy('stock_qty_base_unit', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name ?? 'Tanpa Kategori',
                'stock' => (float) $p->stock_qty_base_unit,
                'base_unit' => $p->baseUnit?->name ?? 'pcs',
                'threshold' => $p->min_stock_threshold,
            ]);

        // D. Indikator Rugi - Owner-only
        $transaksiMerugi = 0;
        if (!$isKaryawan) {
            $transaksiMerugi = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])
                ->whereRaw('total_amount < (SELECT COALESCE(SUM(ti.cost_price_per_base_unit_at_transaction * ti.qty_in_selected_unit * ti.conversion_factor_at_transaction), 0) FROM transaction_items ti WHERE ti.transaction_id = transactions.id)')
                ->count();
        }

        // E. Grafik Penjualan Harian - Scoped & Validated
        $chartPeriod = $request->get('chart_period', '7_hari');
        if (!in_array($chartPeriod, ['7_hari', '14_hari', '30_hari'])) {
            $chartPeriod = '7_hari';
        }

        $chartData = [];
        $daysCount = 7;
        if ($chartPeriod === '14_hari') {
            $daysCount = 14;
        } elseif ($chartPeriod === '30_hari') {
            $daysCount = 30;
        }

        $indonesianDays = [
            'Sunday' => 'Min',
            'Monday' => 'Sen',
            'Tuesday' => 'Sel',
            'Wednesday' => 'Rab',
            'Thursday' => 'Kam',
            'Friday' => 'Jum',
            'Saturday' => 'Sab'
        ];

        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dayQuery = Transaction::whereDate('transaction_datetime', $day->toDateString());
            if ($isKaryawan) {
                $dayQuery->where('cashier_user_id', $user->id);
            }

            $dayOmset = (float) $dayQuery->sum('total_amount');
            $dayCount = $dayQuery->count();

            $dayName = $indonesianDays[$day->format('l')] ?? substr($day->format('D'), 0, 3);
            $label = $dayName . ', ' . $day->format('d/m');
            $fullDate = $day->translatedFormat('l, j F Y');

            $chartData[] = [
                'day' => $dayName,
                'label' => $label,
                'date' => $day->format('d/m'),
                'amount' => $dayOmset,
                'count' => $dayCount,
                'full_date' => $fullDate,
            ];
        }

        // F. Riwayat Transaksi Hari Ini - Scoped & Eager Loaded
        $riwayatQuery = Transaction::whereDate('transaction_datetime', $now->toDateString())
            ->with([
                'cashier:id,name,username',
                'items:id,transaction_id,product_id,qty_in_selected_unit,unit_name_at_transaction',
                'items.product:id,name'
            ]);

        if ($isKaryawan) {
            $riwayatQuery->where('cashier_user_id', $user->id);
        }

        $riwayatHariIni = $riwayatQuery->orderByDesc('transaction_datetime')
            ->limit(10)
            ->get()
            ->map(fn ($txn) => [
                'id' => $txn->id,
                'waktu' => $txn->transaction_datetime->format('H:i') . ' WIB',
                'cashier' => $txn->cashier?->name ?? 'Tidak diketahui',
                'items_summary' => $txn->items->map(fn ($i) => $i->product?->name)->implode(', '),
                'items_count' => $txn->items->count(),
                'total' => (float) $txn->total_amount,
                'payment_method' => $txn->payment_method,
                'discount' => (float) $txn->discount_amount,
            ]);

        return Inertia::render('Dashboard', [
            'period' => $period,
            'chartPeriod' => $chartPeriod,
            'todayCount' => $todayCount,
            'todayOmset' => $todayOmset,
            'totalOmset' => $totalOmset,
            'jumlahTransaksi' => $jumlahTransaksi,
            'totalLabaKotor' => $totalLabaKotor,
            'labaPerProduk' => $labaPerProduk,
            'labaPerKategori' => $labaPerKategori,
            'criticalStockCount' => $criticalStockCount,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'criticalProducts' => $criticalProducts,
            'transaksiMerugi' => $transaksiMerugi,
            'chartData' => $chartData,
            'riwayatHariIni' => $riwayatHariIni,
        ]);
    }
}
