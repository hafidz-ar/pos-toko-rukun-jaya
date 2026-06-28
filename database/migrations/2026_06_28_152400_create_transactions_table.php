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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('member_id')->nullable()->constrained('members');
            $table->decimal('total_revenue', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('discount_applied', 15, 2)->default(0);
            $table->enum('status', ['completed', 'void'])->default('completed');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
