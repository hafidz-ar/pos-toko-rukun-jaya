<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesHistoryController extends Controller
{
    /**
     * Display sales history (Penjualan / Riwayat Transaksi).
     * Owner: all transactions. Karyawan: own transactions only.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaction::query();

        // 1. Validasi Role & Kasir
        $cashierId = $request->get('cashier_id');
        if ($user->role !== 'owner') {
            // Kasir biasa dipaksa hanya melihat transaksi miliknya sendiri
            $query->where('cashier_user_id', $user->id);
            $cashierId = $user->id;
        } elseif ($cashierId && is_numeric($cashierId)) {
            $query->where('cashier_user_id', (int)$cashierId);
        }

        // 2. Filter Metode Pembayaran (Whitelist: semua, tunai, qris)
        $paymentMethod = $request->get('payment_method', 'semua');
        if (!in_array($paymentMethod, ['tunai', 'qris'])) {
            $paymentMethod = 'semua';
        }
        if ($paymentMethod !== 'semua') {
            $query->where('payment_method', $paymentMethod);
        }

        // 3. Filter Tanggal Berpasangan
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $validationError = null;

        if ($dateFrom || $dateTo) {
            if (!$dateFrom || !$dateTo) {
                $validationError = 'Tanggal mulai dan tanggal selesai harus diisi bersamaan.';
            } else {
                try {
                    $start = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                    $end = \Carbon\Carbon::parse($dateTo)->endOfDay();
                    if ($start->gt($end)) {
                        $validationError = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
                    } else {
                        $query->whereBetween('transaction_datetime', [$start, $end]);
                    }
                } catch (\Exception $e) {
                    $validationError = 'Format tanggal tidak valid.';
                }
            }
        }

        // 4. Pencarian Relevan (Grouped Query)
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (preg_match('/^TX-\d+$/i', $search)) {
                    $transactionId = (int) preg_replace('/\D/', '', $search);
                    $q->where('id', $transactionId);
                } elseif (ctype_digit($search)) {
                    $q->where('id', (int) $search);
                } elseif (mb_strlen($search) >= 2) {
                    $q->whereHas('items.product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('cashier', function ($cashierQuery) use ($search) {
                        $cashierQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
                } else {
                    // Jika < 2 karakter teks, paksa hasil kosong agar pencarian tidak dijalankan
                    $q->where('id', 0);
                }
            });
        }

        // 5. Ringkasan Metrik (Summary) - menggunakan clone sebelum pagination
        $filteredQuery = clone $query;
        $filteredQuery->setEagerLoads([]); // mempercepat query count/sum

        $summary = [
            'total_count' => $filteredQuery->count(),
            'total_omset' => (float) (clone $filteredQuery)->sum('total_amount'),
            'total_tunai' => (float) (clone $filteredQuery)->where('payment_method', 'tunai')->sum('total_amount'),
            'total_qris' => (float) (clone $filteredQuery)->where('payment_method', 'qris')->sum('total_amount'),
        ];

        // 6. Whitelist Sorting
        $sortMap = [
            'terbaru' => ['transaction_datetime', 'desc'],
            'terlama' => ['transaction_datetime', 'asc'],
            'harga_tertinggi' => ['total_amount', 'desc'],
            'harga_terendah' => ['total_amount', 'asc'],
        ];

        $sort = $request->get('sort', 'terbaru');
        if (!array_key_exists($sort, $sortMap)) {
            $sort = 'terbaru';
        }
        $sortParams = $sortMap[$sort];
        $query->orderBy($sortParams[0], $sortParams[1]);

        // 7. Pagination & Eager Loading
        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        $transactions = $query->with(['cashier', 'items.product'])
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($txn) => [
                'id' => $txn->id,
                'datetime' => $txn->transaction_datetime->format('d/m/Y H:i'),
                'time' => $txn->transaction_datetime->format('H:i') . ' WIB',
                'date' => $txn->transaction_datetime->format('d/m/Y'),
                'cashier' => $txn->cashier->name,
                'payment_method' => $txn->payment_method,
                'subtotal' => (float) $txn->subtotal_before_discount,
                'discount' => (float) $txn->discount_amount,
                'total' => (float) $txn->total_amount,
                'items_count' => $txn->items->count(),
                'items_summary' => $txn->items->take(3)->map(fn ($i) => $i->product?->name)->implode(', '),
            ]);

        // 8. Kasir list untuk dropdown filter (hanya jika user adalah owner)
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
                'search' => $request->get('search', ''),
                'payment_method' => $paymentMethod,
                'cashier_id' => $request->get('cashier_id', ''),
                'sort' => $sort,
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'per_page' => $perPage,
            ],
            'validationError' => $validationError,
        ]);
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
