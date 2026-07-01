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
        Schema::create('restocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty_base_unit', 15, 2); // qty masuk, sudah dikonversi ke unit dasar
            $table->string('unit_name_at_restock', 50); // unit yang dipakai saat input (untuk tampilan riwayat)
            $table->decimal('cost_price_per_base_unit_at_restock', 15, 2); // HPP batch ini
            $table->string('location', 255); // lokasi penempatan saat restock
            $table->foreignId('restocked_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('restocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restocks');
    }
};
