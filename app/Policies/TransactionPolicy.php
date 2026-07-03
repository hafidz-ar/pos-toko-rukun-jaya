<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // Owner has access to all transactions
        if ($user->role === 'owner') {
            return true;
        }

        // Karyawan can only view their own transactions
        return (int)$transaction->cashier_user_id === (int)$user->id;
    }
}
