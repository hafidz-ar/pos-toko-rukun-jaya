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
        $reportData = $this->getReportData($request);

        $perPage = $reportData['filters']['per_page'] ?? 10;
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }
        $labaPerProduk = $reportData['labaPerProduk'];

        // Paginate $labaPerProduk in-memory
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $paginatedItems = $labaPerProduk->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();
        $labaPerProdukPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $labaPerProduk->count(),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Laporan', [
            'period' => $reportData['period'],
            'preset' => $reportData['preset'],
            'periodLabel' => $reportData['periodLabel'],
            'comparisonLabel' => $reportData['comparisonLabel'],
            'comparisonDateLabel' => $reportData['comparisonStartDate']->format('d/m/Y') . ' - ' . $reportData['comparisonEndDate']->format('d/m/Y'),
            'summary' => $reportData['summary'],
            'labaPerProduk' => $labaPerProdukPaginated,
            'labaPerKategori' => $reportData['labaPerKategori'],
            'filters' => $reportData['filters'],
            'validationError' => $reportData['validationError'],
        ]);
    }

    public function exportPdf(Request $request)
    {
        $reportData = $this->getReportData($request);

        return view('reports.laporan-print', [
            'periodLabel' => $reportData['periodLabel'],
            'comparisonLabel' => $reportData['comparisonLabel'],
            'comparisonDateLabel' => $reportData['comparisonStartDate']->format('d/m/Y') . ' - ' . $reportData['comparisonEndDate']->format('d/m/Y'),
            'generatedAt' => Carbon::now('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB',
            'summary' => $reportData['summary'],
            'labaPerProduk' => $reportData['labaPerProduk'],
            'labaPerKategori' => $reportData['labaPerKategori'],
        ]);
    }

    /**
     * Calculate all report numbers based on filtered dates.
     */
    private function getReportData(Request $request)
    {
        $period = $request->get('period');
        $preset = $request->get('preset');
        $now = Carbon::now('Asia/Jakarta');
        
        $validationError = null;

        // Fallback: period kosong -> tampilkan minggu ini
        if (!$period) {
            $period = 'mingguan';
            $preset = 'minggu_ini';
        }

        // Validate period
        if (!in_array($period, ['harian', 'mingguan', 'bulanan', 'tahunan', 'kustom'])) {
            $period = 'mingguan';
            $preset = 'minggu_ini';
        }

        // Validate preset based on period
        if ($period === 'harian') {
            if ($preset && !in_array($preset, ['hari_ini', 'kemarin'])) {
                $preset = 'hari_ini';
            }
        } elseif ($period === 'mingguan') {
            if ($preset && !in_array($preset, ['minggu_ini', 'minggu_lalu'])) {
                $preset = 'minggu_ini';
            }
        } elseif ($period === 'bulanan') {
            if ($preset && !in_array($preset, ['bulan_ini', 'bulan_lalu'])) {
                $preset = 'bulan_ini';
            }
        } elseif ($period === 'tahunan') {
            if ($preset && !in_array($preset, ['tahun_ini', 'tahun_lalu'])) {
                $preset = 'tahun_ini';
            }
        }

        $startDate = null;
        $endDate = null;
        $periodLabel = '';
        $comparisonLabel = '';
        $prevStartDate = null;
        $prevEndDate = null;

        if ($period === 'harian') {
            if ($preset === 'kemarin') {
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $periodLabel = 'Kemarin (' . $startDate->format('d/m/Y') . ')';
                $comparisonLabel = 'kemarin';
            } elseif ($request->has('date') && !empty($request->get('date'))) {
                $dateStr = $request->get('date');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                    $targetDate = Carbon::parse($dateStr, 'Asia/Jakarta');
                    $startDate = $targetDate->copy()->startOfDay();
                    $endDate = $targetDate->copy()->endOfDay();
                    $periodLabel = $targetDate->format('d/m/Y');
                    $comparisonLabel = 'hari sebelumnya';
                } else {
                    $validationError = 'Format tanggal harian tidak valid. Menggunakan hari ini.';
                }
            }
            
            if (!$startDate) {
                $preset = 'hari_ini';
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hari Ini (' . $now->format('d/m/Y') . ')';
                $comparisonLabel = 'hari sebelumnya';
            }

            // Comparison period: 1 day before
            $prevStartDate = $startDate->copy()->subDay()->startOfDay();
            $prevEndDate = $startDate->copy()->subDay()->endOfDay();

        } elseif ($period === 'mingguan') {
            if ($preset === 'minggu_lalu') {
                $startDate = $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $endDate = $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $periodLabel = 'Minggu Lalu (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                $comparisonLabel = 'minggu lalu';
            } elseif ($request->has('date') && !empty($request->get('date'))) {
                $dateStr = $request->get('date');
                // Could be date within week or week pattern YYYY-Www
                if (preg_match('/^\d{4}-W\d{2}$/', $dateStr) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                    $targetDate = Carbon::parse($dateStr, 'Asia/Jakarta');
                    $startDate = $targetDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                    $endDate = $targetDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                    $periodLabel = 'Minggu (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                    $comparisonLabel = 'minggu sebelumnya';
                } else {
                    $validationError = 'Format tanggal mingguan tidak valid. Menggunakan minggu ini.';
                }
            }

            if (!$startDate) {
                $preset = 'minggu_ini';
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $periodLabel = 'Minggu Ini (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                $comparisonLabel = 'minggu lalu';
            }

            // Comparison period: 1 week before
            $prevStartDate = $startDate->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $prevEndDate = $endDate->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        } elseif ($period === 'bulanan') {
            if ($preset === 'bulan_lalu') {
                $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                $periodLabel = 'Bulan Lalu (' . $startDate->translatedFormat('F Y') . ')';
                $comparisonLabel = 'bulan lalu';
            } elseif ($request->has('month') && $request->has('year') && !empty($request->get('month')) && !empty($request->get('year'))) {
                $month = $request->integer('month');
                $year = $request->integer('year');
                $currentYear = $now->year;

                if ($month >= 1 && $month <= 12 && $year >= 2020 && $year <= ($currentYear + 1)) {
                    $startDate = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Jakarta')->startOfDay();
                    $endDate = $startDate->copy()->endOfMonth()->endOfDay();
                    $periodLabel = $startDate->translatedFormat('F Y');
                    $comparisonLabel = 'bulan sebelumnya';
                } else {
                    $validationError = 'Parameter bulan/tahun tidak valid. Menggunakan bulan ini.';
                }
            }

            if (!$startDate) {
                $preset = 'bulan_ini';
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->endOfMonth()->endOfDay();
                $periodLabel = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                $comparisonLabel = 'bulan lalu';
            }

            // Comparison period: 1 month before
            $prevStartDate = $startDate->copy()->subMonth()->startOfMonth()->startOfDay();
            $prevEndDate = $startDate->copy()->subMonth()->endOfMonth()->endOfDay();

        } elseif ($period === 'tahunan') {
            if ($preset === 'tahun_lalu') {
                $startDate = $now->copy()->subYear()->startOfYear()->startOfDay();
                $endDate = $now->copy()->subYear()->endOfYear()->endOfDay();
                $periodLabel = 'Tahun Lalu (' . $startDate->format('Y') . ')';
                $comparisonLabel = 'tahun lalu';
            } elseif ($request->has('year') && !empty($request->get('year'))) {
                $year = $request->integer('year');
                $currentYear = $now->year;

                if ($year >= 2020 && $year <= ($currentYear + 1)) {
                    $startDate = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Jakarta')->startOfDay();
                    $endDate = Carbon::create($year, 12, 31, 23, 59, 59, 'Asia/Jakarta')->endOfDay();
                    $periodLabel = 'Tahun ' . $year;
                    $comparisonLabel = 'tahun sebelumnya';
                } else {
                    $validationError = 'Parameter tahun tidak valid. Menggunakan tahun ini.';
                }
            }

            if (!$startDate) {
                $preset = 'tahun_ini';
                $startDate = $now->copy()->startOfYear()->startOfDay();
                $endDate = $now->copy()->endOfYear()->endOfDay();
                $periodLabel = 'Tahun Ini (' . $now->format('Y') . ')';
                $comparisonLabel = 'tahun lalu';
            }

            // Comparison period: 1 year before
            $prevStartDate = $startDate->copy()->subYear()->startOfYear()->startOfDay();
            $prevEndDate = $startDate->copy()->subYear()->endOfYear()->endOfDay();

        } else { // kustom
            $startInput = $request->get('start_date');
            $endInput = $request->get('end_date');

            if ($startInput && $endInput) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endInput)) {
                    $startDate = Carbon::parse($startInput, 'Asia/Jakarta')->startOfDay();
                    $endDate = Carbon::parse($endInput, 'Asia/Jakarta')->endOfDay();

                    if ($startDate->greaterThan($endDate)) {
                        $validationError = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
                        $startDate = null; // force fallback to mingguan
                    } elseif ($startDate->diffInDays($endDate) > 366) {
                        $validationError = 'Rentang waktu kustom maksimal adalah 1 tahun (366 hari).';
                        $startDate = null; // force fallback to mingguan
                    } else {
                        $periodLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                        $comparisonLabel = 'periode sebelumnya';
                    }
                } else {
                    $validationError = 'Format tanggal kustom tidak valid. Menampilkan minggu ini.';
                }
            } else {
                $validationError = 'Tanggal mulai dan tanggal selesai harus diisi untuk periode kustom.';
            }

            // Fallback to mingguan (Minggu Ini)
            if (!$startDate) {
                $period = 'mingguan';
                $preset = 'minggu_ini';
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $periodLabel = 'Minggu Ini (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                $comparisonLabel = 'minggu lalu';
                $prevStartDate = $startDate->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $prevEndDate = $endDate->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            } else {
                // Kustom comparison range:
                // durasi = end_date - start_date + 1 hari
                // comparison_end = start_date - 1 hari
                // comparison_start = comparison_end - durasi + 1 hari
                $daysDiff = $startDate->diffInDays($endDate) + 1;
                $prevEndDate = $startDate->copy()->subDay()->endOfDay();
                $prevStartDate = $prevEndDate->copy()->subDays($daysDiff - 1)->startOfDay();
            }
        }

        // --- Fetch transactions and calculate statistics ---
        
        // 1. Current Period
        $transactions = Transaction::whereBetween('transaction_datetime', [$startDate, $endDate])->get();
        $transactionItems = TransactionItem::whereHas('transaction', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('transaction_datetime', [$startDate, $endDate]);
        })->with('product.category')->get();

        $totalOmset = $transactions->sum('total_amount');
        $jumlahTransaksi = $transactions->count();
        $totalDiskon = $transactions->sum('discount_amount');

        $totalHPP = $transactionItems->sum(function ($item) {
            return $item->cost_price_per_base_unit_at_transaction * $item->qty_in_selected_unit * $item->conversion_factor_at_transaction;
        });
        $totalLabaKotor = $transactionItems->sum(fn ($item) => $item->profit);
        $marginLabaKotor = $totalOmset > 0 ? ($totalLabaKotor / $totalOmset) * 100 : 0;

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

        // Anomaly / loss indicators
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

        // 2. Previous Period (for Comparison)
        $prevTransactions = Transaction::whereBetween('transaction_datetime', [$prevStartDate, $prevEndDate])->get();
        $prevTransactionItems = TransactionItem::whereHas('transaction', function ($q) use ($prevStartDate, $prevEndDate) {
            $q->whereBetween('transaction_datetime', [$prevStartDate, $prevEndDate]);
        })->get();

        $prevTotalOmset = $prevTransactions->sum('total_amount');
        $prevJumlahTransaksi = $prevTransactions->count();
        $prevTotalLabaKotor = $prevTransactionItems->sum(fn ($item) => $item->profit);
        $prevMarginLabaKotor = $prevTotalOmset > 0 ? ($prevTotalLabaKotor / $prevTotalOmset) * 100 : 0;

        // Metric changes generator function
        $calcChangeMetric = function ($current, $previous, $type = 'regular') use ($comparisonLabel) {
            if ($type === 'margin') {
                $diff = $current - $previous;
                if ($previous == 0 && $current == 0) {
                    return [
                        'previous_value' => round($previous, 1),
                        'percentage' => 0.0,
                        'status' => 'unchanged',
                        'label' => $comparisonLabel,
                    ];
                }
                if ($previous == 0 && $current > 0) {
                    return [
                        'previous_value' => round($previous, 1),
                        'percentage' => round($diff, 1),
                        'status' => 'new_data',
                        'label' => $comparisonLabel,
                    ];
                }
                return [
                    'previous_value' => round($previous, 1),
                    'percentage' => round(abs($diff), 1),
                    'status' => $diff > 0 ? 'increase' : ($diff < 0 ? 'decrease' : 'unchanged'),
                    'label' => $comparisonLabel,
                ];
            } else {
                if ($previous == 0) {
                    if ($current == 0) {
                        return [
                            'previous_value' => round($previous, 2),
                            'percentage' => 0.0,
                            'status' => 'unchanged',
                            'label' => $comparisonLabel,
                        ];
                    }
                    return [
                        'previous_value' => round($previous, 2),
                        'percentage' => 100.0,
                        'status' => 'new_data',
                        'label' => $comparisonLabel,
                    ];
                }
                $pct = (($current - $previous) / $previous) * 100;
                return [
                    'previous_value' => round($previous, 2),
                    'percentage' => round(abs($pct), 1),
                    'status' => $pct > 0 ? 'increase' : ($pct < 0 ? 'decrease' : 'unchanged'),
                    'label' => $comparisonLabel,
                ];
            }
        };

        $omsetComparison = $calcChangeMetric($totalOmset, $prevTotalOmset, 'regular');
        $labaKotorComparison = $calcChangeMetric($totalLabaKotor, $prevTotalLabaKotor, 'regular');
        $jumlahTransaksiComparison = $calcChangeMetric($jumlahTransaksi, $prevJumlahTransaksi, 'regular');
        $marginComparison = $calcChangeMetric($marginLabaKotor, $prevMarginLabaKotor, 'margin');

        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        return [
            'period' => $period,
            'preset' => $preset,
            'periodLabel' => $periodLabel,
            'comparisonLabel' => $comparisonLabel,
            'comparisonStartDate' => $prevStartDate,
            'comparisonEndDate' => $prevEndDate,
            'summary' => [
                'total_omset' => round($totalOmset, 2),
                'jumlah_transaksi' => $jumlahTransaksi,
                'total_diskon' => round($totalDiskon, 2),
                'total_hpp' => round($totalHPP, 2),
                'total_laba_kotor' => round($totalLabaKotor, 2),
                'margin_laba_kotor' => round($marginLabaKotor, 1),
                'transaksi_merugi' => $transaksiMerugi,
                'per_metode_bayar' => $perMetodeBayar,
                'comparison' => [
                    'omset' => $omsetComparison,
                    'laba_kotor' => $labaKotorComparison,
                    'jumlah_transaksi' => $jumlahTransaksiComparison,
                    'margin' => $marginComparison,
                ]
            ],
            'labaPerProduk' => $labaPerProduk,
            'labaPerKategori' => $labaPerKategori,
            'filters' => [
                'period' => $period,
                'preset' => $preset,
                'date' => $request->get('date'),
                'month' => $request->get('month'),
                'year' => $request->get('year'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'per_page' => $request->has('per_page') ? $perPage : null,
            ],
            'validationError' => $validationError,
        ];
    }
}
