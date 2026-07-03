<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['cashier_user_id', 'transaction_datetime'],
                'transactions_cashier_datetime_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Recreate the single index first so MySQL does not block dropping the composite index
            $table->index('cashier_user_id');
            $table->dropIndex('transactions_cashier_datetime_index');
        });
    }
};
