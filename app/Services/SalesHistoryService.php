<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SalesHistoryService
{
    /**
     * Normalize filters based on user role.
     */
    public function normalizeFilters(User $user, array $filters): array
    {
        if ($user->role !== 'owner') {
            $filters['cashier_user_id'] = $user->id;
        }

        return $filters;
    }

    /**
     * Build the filtered transaction query.
     */
    public function buildQuery(User $user, array $filters): Builder
    {
        // Normalize first
        $filters = $this->normalizeFilters($user, $filters);

        $query = Transaction::query()
            ->with([
                'items.product',
                'cashier',
            ]);

        // 1. Pencarian Universal
        $search = isset($filters['search']) ? trim($filters['search']) : '';
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                if (preg_match('/^TX-\d+$/i', $search)) {
                    $transactionId = (int) preg_replace('/\D/', '', $search);
                    $query->where('id', $transactionId);
                } elseif (ctype_digit($search)) {
                    $query->where('id', (int) $search);
                } elseif (mb_strlen($search) >= 2) {
                    $query->whereHas('items.product', function (Builder $productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('cashier', function (Builder $cashierQuery) use ($search) {
                        $cashierQuery->where('name', 'like', "%{$search}%");
                    });
                } else {
                    $query->where('id', 0);
                }
            });
        }

        // 2. Metode Pembayaran
        if (!empty($filters['payment_method']) && in_array($filters['payment_method'], ['tunai', 'qris'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // 3. Kasir
        if (!empty($filters['cashier_user_id']) && is_numeric($filters['cashier_user_id'])) {
            $query->where('cashier_user_id', (int) $filters['cashier_user_id']);
        }

        // 4. Rentang Tanggal
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            try {
                $start = Carbon::parse($filters['date_from'])->startOfDay();
                $end = Carbon::parse($filters['date_to'])->endOfDay();
                if ($start->lte($end)) {
                    $query->whereBetween('transaction_datetime', [$start, $end]);
                }
            } catch (\Exception $e) {
                // Ignore invalid date formats
            }
        }

        // 5. Urutan (Sorting)
        switch ($filters['sort'] ?? 'latest') {
            case 'oldest':
                $query->orderBy('transaction_datetime', 'asc');
                break;
            case 'highest_price':
                $query->orderBy('total_amount', 'desc')
                    ->orderBy('transaction_datetime', 'desc');
                break;
            case 'lowest_price':
                $query->orderBy('total_amount', 'asc')
                    ->orderBy('transaction_datetime', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('transaction_datetime', 'desc');
                break;
        }

        return $query;
    }
}
