<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display report page.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'bulan');
        $now = Carbon::now();

        switch ($period) {
            case 'harian':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hari Ini (' . $now->format('d/m/Y') . ')';
                break;
            case 'minggu':
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
                $periodLabel = 'Minggu Ini (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                break;
            case 'bulan':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodLabel = $now->translatedFormat('F Y');
                break;
        }

        // Transactions in period
        $transactions = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])->get();
        $transactionItems = TransactionItem::whereHas('transaction', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('transaction_datetime', [$startDate, $endDate]);
        })->with('product.category')->get();

        // Summary
        $totalOmset = $transactions->sum('total_amount');
        $jumlahTransaksi = $transactions->count();
        $totalDiskon = $transactions->sum('discount_amount');

        // HPP & Laba Kotor
        $totalHPP = $transactionItems->sum(function ($item) {
            return $item->cost_price_per_base_unit_at_transaction * $item->qty_in_selected_unit * $item->conversion_factor_at_transaction;
        });
        $totalLabaKotor = $transactionItems->sum(fn ($item) => $item->profit);

        // Breakdown per produk
        $labaPerProduk = $transactionItems->groupBy('product_id')->map(function ($items) {
            $first = $items->first();
            $revenue = $items->sum(fn ($i) => $i->subtotal);
            $profit = $items->sum(fn ($i) => $i->profit);
            $hpp = $items->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            return [
                'product_id' => $first->product_id,
                'product_name' => $first->product?->name ?? 'Produk Dihapus',
                'category' => $first->product?->category?->name ?? '-',
                'total_revenue' => round($revenue, 2),
                'total_hpp' => round($hpp, 2),
                'total_profit' => round($profit, 2),
                'margin_pct' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->sortByDesc('total_profit')->values();

        // Breakdown per kategori
        $labaPerKategori = $transactionItems->groupBy(fn ($item) => $item->product?->category_id)->map(function ($items) {
            $first = $items->first();
            $revenue = $items->sum(fn ($i) => $i->subtotal);
            $profit = $items->sum(fn ($i) => $i->profit);
            $hpp = $items->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            return [
                'category_name' => $first->product?->category?->name ?? 'Tanpa Kategori',
                'total_revenue' => round($revenue, 2),
                'total_hpp' => round($hpp, 2),
                'total_profit' => round($profit, 2),
                'margin_pct' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->sortByDesc('total_profit')->values();

        // Indikator rugi (transaksi merugi count)
        $transaksiMerugi = 0;
        foreach ($transactions as $txn) {
            $txnItems = $transactionItems->where('transaction_id', $txn->id);
            $txnHPP = $txnItems->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            if ($txn->total_amount < $txnHPP) {
                $transaksiMerugi++;
            }
        }

        // Payment method breakdown
        $perMetodeBayar = [
            'tunai' => [
                'count' => $transactions->where('payment_method', 'tunai')->count(),
                'total' => $transactions->where('payment_method', 'tunai')->sum('total_amount'),
            ],
            'qris' => [
                'count' => $transactions->where('payment_method', 'qris')->count(),
                'total' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
            ],
        ];

        return Inertia::render('Laporan', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'summary' => [
                'total_omset' => round($totalOmset, 2),
                'jumlah_transaksi' => $jumlahTransaksi,
                'total_diskon' => round($totalDiskon, 2),
                'total_hpp' => round($totalHPP, 2),
                'total_laba_kotor' => round($totalLabaKotor, 2),
                'transaksi_merugi' => $transaksiMerugi,
                'per_metode_bayar' => $perMetodeBayar,
            ],
            'labaPerProduk' => $labaPerProduk,
            'labaPerKategori' => $labaPerKategori,
        ]);
    }

    /**
     * Export report as PDF (PRD 3.3).
     */
    public function exportPdf(Request $request)
    {
        $period = $request->get('period', 'bulan');
        $now = Carbon::now();

        switch ($period) {
            case 'harian':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hari Ini (' . $now->format('d/m/Y') . ')';
                break;
            case 'minggu':
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
                $periodLabel = 'Minggu Ini (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                break;
            case 'bulan':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodLabel = $now->translatedFormat('F Y');
                break;
        }

        // Transactions in period
        $transactions = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])->get();
        $transactionItems = TransactionItem::whereHas('transaction', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('transaction_datetime', [$startDate, $endDate]);
        })->with('product.category')->get();

        // Summary
        $totalOmset = $transactions->sum('total_amount');
        $jumlahTransaksi = $transactions->count();
        $totalDiskon = $transactions->sum('discount_amount');

        // HPP & Laba Kotor
        $totalHPP = $transactionItems->sum(function ($item) {
            return $item->cost_price_per_base_unit_at_transaction * $item->qty_in_selected_unit * $item->conversion_factor_at_transaction;
        });
        $totalLabaKotor = $transactionItems->sum(fn ($item) => $item->profit);

        // Breakdown per produk
        $labaPerProduk = $transactionItems->groupBy('product_id')->map(function ($items) {
            $first = $items->first();
            $revenue = $items->sum(fn ($i) => $i->subtotal);
            $profit = $items->sum(fn ($i) => $i->profit);
            $hpp = $items->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            return [
                'product_name' => $first->product?->name ?? 'Produk Dihapus',
                'category' => $first->product?->category?->name ?? '-',
                'total_revenue' => round($revenue, 2),
                'total_hpp' => round($hpp, 2),
                'total_profit' => round($profit, 2),
                'margin_pct' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->sortByDesc('total_profit')->values();

        // Breakdown per kategori
        $labaPerKategori = $transactionItems->groupBy(fn ($item) => $item->product?->category_id)->map(function ($items) {
            $first = $items->first();
            $revenue = $items->sum(fn ($i) => $i->subtotal);
            $profit = $items->sum(fn ($i) => $i->profit);
            $hpp = $items->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            return [
                'category_name' => $first->product?->category?->name ?? 'Tanpa Kategori',
                'total_revenue' => round($revenue, 2),
                'total_hpp' => round($hpp, 2),
                'total_profit' => round($profit, 2),
                'margin_pct' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            ];
        })->sortByDesc('total_profit')->values();

        // Indikator rugi
        $transaksiMerugi = 0;
        foreach ($transactions as $txn) {
            $txnItems = $transactionItems->where('transaction_id', $txn->id);
            $txnHPP = $txnItems->sum(function ($i) {
                return $i->cost_price_per_base_unit_at_transaction * $i->qty_in_selected_unit * $i->conversion_factor_at_transaction;
            });
            if ($txn->total_amount < $txnHPP) {
                $transaksiMerugi++;
            }
        }

        // Payment method breakdown
        $perMetodeBayar = [
            'tunai' => [
                'count' => $transactions->where('payment_method', 'tunai')->count(),
                'total' => $transactions->where('payment_method', 'tunai')->sum('total_amount'),
            ],
            'qris' => [
                'count' => $transactions->where('payment_method', 'qris')->count(),
                'total' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
            ],
        ];

        $summary = [
            'total_omset' => round($totalOmset, 2),
            'jumlah_transaksi' => $jumlahTransaksi,
            'total_diskon' => round($totalDiskon, 2),
            'total_hpp' => round($totalHPP, 2),
            'total_laba_kotor' => round($totalLabaKotor, 2),
            'transaksi_merugi' => $transaksiMerugi,
            'per_metode_bayar' => $perMetodeBayar,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.laporan-pdf', [
            'periodLabel' => $periodLabel,
            'generatedAt' => $now->format('d/m/Y H:i') . ' WIB',
            'summary' => $summary,
            'labaPerProduk' => $labaPerProduk,
            'labaPerKategori' => $labaPerKategori,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $safePeriodLabel = str_replace([' ', '/', '\\'], '_', $periodLabel);
        $filename = 'Laporan_Keuangan_' . $safePeriodLabel . '.pdf';

        return $pdf->download($filename);
    }
}
