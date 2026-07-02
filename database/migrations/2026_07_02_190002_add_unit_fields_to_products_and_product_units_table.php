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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('base_unit_id')->nullable()->after('category_id');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('product_id');
            $table->decimal('selling_price', 15, 2)->nullable()->after('conversion_factor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'selling_price']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['base_unit_id']);
        });
    }
};
