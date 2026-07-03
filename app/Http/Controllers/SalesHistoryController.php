<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SalesHistoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesHistoryController extends Controller
{
    public function __construct(
        protected SalesHistoryService $salesHistoryService
    ) {}

    /**
     * Validate common filter request parameters.
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:tunai,qris'],
            'cashier_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:latest,oldest,highest_price,lowest_price'],
        ]);
    }

    /**
     * Display sales history (Penjualan / Riwayat Transaksi).
     * Owner: all transactions. Karyawan: own transactions only.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $filters = $this->validatedFilters($request);
        $filters = $this->salesHistoryService->normalizeFilters($user, $filters);

        // Date pair validation check for banner UI warning
        $validationError = null;
        if (($request->filled('date_from') && !$request->filled('date_to')) || (!$request->filled('date_from') && $request->filled('date_to'))) {
            $validationError = 'Tanggal mulai dan tanggal selesai harus diisi bersamaan.';
        }

        $query = $this->salesHistoryService->buildQuery($user, $filters);

        // Ringkasan Metrik (Summary) - menggunakan clone sebelum pagination dan eager loading
        $filteredQuery = clone $query;
        $filteredQuery->setEagerLoads([]); // mempercepat query count/sum

        $summary = [
            'total_count' => $filteredQuery->count(),
            'total_omset' => (float) (clone $filteredQuery)->sum('total_amount'),
            'total_tunai' => (float) (clone $filteredQuery)->where('payment_method', 'tunai')->sum('total_amount'),
            'total_qris' => (float) (clone $filteredQuery)->where('payment_method', 'qris')->sum('total_amount'),
        ];

        // Pagination
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        $transactions = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($txn) => [
                'id' => $txn->id,
                'datetime' => $txn->transaction_datetime->format('d/m/Y H:i'),
                'time' => $txn->transaction_datetime->format('H:i') . ' WIB',
                'date' => $txn->transaction_datetime->format('d/m/Y'),
                'cashier' => $txn->cashier?->name ?? '-',
                'payment_method' => $txn->payment_method,
                'subtotal' => (float) $txn->subtotal_before_discount,
                'discount' => (float) $txn->discount_amount,
                'total' => (float) $txn->total_amount,
                'items_count' => $txn->items->count(),
                'items_summary' => $txn->items->take(3)->map(fn ($i) => $i->product?->name)->implode(', '),
            ]);

        // Kasir list untuk dropdown filter (hanya jika user adalah owner)
        $cashiers = [];
        if ($user->role === 'owner') {
            $cashiers = \App\Models\User::whereIn('role', ['owner', 'karyawan'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'username']);
        }

        return Inertia::render('Penjualan', [
            'transactions' => $transactions,
            'cashiers' => $cashiers,
            'summary' => $summary,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'payment_method' => $filters['payment_method'] ?? '',
                'cashier_user_id' => $filters['cashier_user_id'] ?? '',
                'sort' => $filters['sort'] ?? 'latest',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'per_page' => $perPage,
            ],
            'validationError' => $validationError,
        ]);
    }

    /**
     * Export sales history data to Excel.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $filters = $this->validatedFilters($request);
        $filters = $this->salesHistoryService->normalizeFilters($user, $filters);

        // Date pair validation check
        if (($request->filled('date_from') && !$request->filled('date_to')) || (!$request->filled('date_from') && $request->filled('date_to'))) {
            return back()->withErrors([
                'export' => 'Tanggal mulai dan tanggal selesai harus diisi bersamaan.',
            ]);
        }

        $query = $this->salesHistoryService->buildQuery($user, $filters);

        // Check if data exists before export
        if (!$query->exists()) {
            return back()->withErrors([
                'export' => 'Tidak ada transaksi untuk diekspor.',
            ]);
        }

        // Calculate summary metrik using clone query
        $metricQuery = clone $query;
        $metricQuery->setEagerLoads([]);

        $summary = [
            'total_count' => $metricQuery->count(),
            'total_revenue' => (float) (clone $metricQuery)->sum('total_amount'),
            'cash_total' => (float) (clone $metricQuery)->where('payment_method', 'tunai')->sum('total_amount'),
            'qris_total' => (float) (clone $metricQuery)->where('payment_method', 'qris')->sum('total_amount'),
        ];

        // Format cashier name for summary
        $selectedCashierName = 'Semua Kasir';
        if ($user->role !== 'owner') {
            $selectedCashierName = $user->name;
        } elseif (!empty($filters['cashier_user_id'])) {
            $selectedCashierName = \App\Models\User::find($filters['cashier_user_id'])?->name ?? '-';
        }

        // Format dynamic filename
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        if ($dateFrom && $dateTo) {
            $fileName = 'riwayat-penjualan-dari_' . $dateFrom . '_sampai_' . $dateTo . '.xlsx';
        } else {
            $fileName = 'riwayat-penjualan-' . now()->format('Y-m-d') . '.xlsx';
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesHistoryExport($query, $summary, $filters, $selectedCashierName),
            $fileName
        );
    }

    /**
     * Show transaction detail (read-only).
     */
    public function show(Request $request, Transaction $transaction)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $transaction);

        $transaction->load(['cashier', 'items.product']);

        return response()->json([
            'id' => $transaction->id,
            'datetime' => $transaction->transaction_datetime->format('d/m/Y H:i'),
            'time' => $transaction->transaction_datetime->format('H:i') . ' WIB',
            'date' => $transaction->transaction_datetime->format('d/m/Y'),
            'cashier' => $transaction->cashier->name,
            'payment_method' => $transaction->payment_method,
            'subtotal' => (float) $transaction->subtotal_before_discount,
            'discount' => (float) $transaction->discount_amount,
            'total' => (float) $transaction->total_amount,
            'cash_received' => (float) $transaction->cash_received,
            'change' => $transaction->payment_method === 'tunai' 
                ? (float) max(0, $transaction->cash_received - $transaction->total_amount) 
                : 0.0,
            'items' => $transaction->items->map(fn ($i) => [
                'product_name' => $i->product?->name ?? 'Produk Dihapus',
                'unit' => $i->unit_name_at_transaction,
                'qty' => (float) $i->qty_in_selected_unit,
                'price' => (float) $i->price_per_unit_at_transaction,
                'subtotal' => (float) $i->subtotal,
            ]),
        ]);
    }
}
