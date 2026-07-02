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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('base_unit', 50); // unit terkecil, mis. "kg", "buah", "meter"
            $table->decimal('cost_price_per_base_unit', 15, 2)->default(0); // HPP per unit dasar (weighted avg)
            $table->decimal('selling_price_per_base_unit', 15, 2)->default(0); // harga jual per unit dasar
            $table->decimal('stock_qty_base_unit', 15, 2)->default(0); // stok dalam unit dasar
            $table->string('location', 255)->nullable(); // rak/zona penempatan terkini
            $table->string('photo_url', 500)->nullable(); // URL foto eksternal (Cloudinary)
            $table->integer('min_stock_threshold')->default(10); // threshold stok menipis
            $table->boolean('is_active')->default(true); // soft delete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
