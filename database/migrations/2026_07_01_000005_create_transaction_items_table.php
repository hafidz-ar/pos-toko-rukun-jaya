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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('unit_name_at_transaction', 50); // satuan yang dipakai kasir (mis. "sak")
            $table->decimal('qty_in_selected_unit', 15, 2); // qty dalam satuan yang dipilih kasir
            $table->decimal('conversion_factor_at_transaction', 15, 4); // snapshot faktor konversi
            $table->decimal('price_per_unit_at_transaction', 15, 2); // snapshot harga per unit yang dipilih
            $table->decimal('cost_price_per_base_unit_at_transaction', 15, 2); // snapshot HPP per unit dasar
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
