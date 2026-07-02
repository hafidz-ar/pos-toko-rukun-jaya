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
        // 1. Set columns to NOT NULL and add foreign key constraints
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('base_unit_id')->nullable(false)->change();
            $table->foreign('base_unit_id')
                ->references('id')
                ->on('units')
                ->restrictOnDelete();
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->restrictOnDelete();

            $table->unique(['product_id', 'unit_id']);
        });

        // 2. Cleanup legacy columns
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'units')) {
                $table->dropColumn('units');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'base_unit')) {
                $table->dropColumn('base_unit');
            }
        });

        Schema::table('product_units', function (Blueprint $table) {
            if (Schema::hasColumn('product_units', 'unit_name')) {
                $table->dropColumn('unit_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate dropped columns as nullable string
        Schema::table('categories', function (Blueprint $table) {
            $table->string('units', 255)->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('base_unit', 50)->nullable();
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->string('unit_name', 50)->nullable();
        });

        // Drop foreign keys and set to nullable
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->unsignedBigInteger('base_unit_id')->nullable()->change();
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropUnique(['product_id', 'unit_id']);
            $table->unsignedBigInteger('unit_id')->nullable()->change();
        });
    }
};
