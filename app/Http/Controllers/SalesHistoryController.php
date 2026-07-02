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
        $query = Transaction::with(['cashier', 'items.product']);

        // Karyawan only sees own transactions
        if ($user->isKaryawan()) {
            $query->where('cashier_user_id', $user->id);
        }

        // Filter by date range
        if ($from = $request->get('date_from')) {
            $query->whereDate('transaction_datetime', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('transaction_datetime', '<=', $to);
        }

        $perPage = $request->integer('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 10;
        }

        $transactions = $query->orderByDesc('transaction_datetime')
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

        return Inertia::render('Penjualan', [
            'transactions' => $transactions,
            'filters' => [
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show transaction detail (read-only).
     */
    public function show(Request $request, Transaction $transaction)
    {
        $user = $request->user();

        // Karyawan can only view own transactions
        if ($user->isKaryawan() && $transaction->cashier_user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $transaction->load(['cashier', 'items.product']);

        return response()->json([
            'id' => $transaction->id,
            'datetime' => $transaction->transaction_datetime->format('d/m/Y H:i'),
            'cashier' => $transaction->cashier->name,
            'payment_method' => $transaction->payment_method,
            'subtotal' => (float) $transaction->subtotal_before_discount,
            'discount' => (float) $transaction->discount_amount,
            'total' => (float) $transaction->total_amount,
            'items' => $transaction->items->map(fn ($i) => [
                'product_name' => $i->product?->name ?? 'Produk Dihapus',
                'unit' => $i->unit_name_at_transaction,
                'qty' => (float) $i->qty_in_selected_unit,
                'price' => (float) $i->price_per_unit_at_transaction,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }
}
