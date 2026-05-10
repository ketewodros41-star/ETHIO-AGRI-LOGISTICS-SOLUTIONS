<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aggregation_lot_contributions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('lot_id');
            $table->string('farmer_id');
            $table->string('company_id');
            $table->decimal('contributed_kg', 12, 3);
            $table->enum('quality_grade', ['A','B','C','ungraded'])->default('ungraded');
            $table->decimal('price_per_kg_etb', 10, 2)->nullable();
            $table->decimal('gross_payout_etb', 12, 2)->nullable();
            $table->decimal('commission_etb', 12, 2)->nullable();
            $table->decimal('net_payout_etb', 12, 2)->nullable();
            $table->string('payout_transaction_id')->nullable();
            $table->enum('payout_status', ['pending','processing','paid','failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'farmer_id']);
            $table->index(['company_id', 'payout_status']);
            $table->index('farmer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregation_lot_contributions');
    }
};
