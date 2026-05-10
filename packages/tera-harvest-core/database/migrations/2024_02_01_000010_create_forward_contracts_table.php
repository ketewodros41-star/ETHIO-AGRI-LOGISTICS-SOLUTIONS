<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('forward_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('contract_number')->unique();
            $table->string('company_id');
            $table->string('buyer_id');
            $table->string('commodity');
            $table->decimal('quantity_kg', 12, 3);
            $table->decimal('price_per_kg_etb', 10, 2);
            $table->decimal('total_value_etb', 12, 2);
            $table->decimal('deposit_pct', 5, 2)->default('10.00');
            $table->decimal('deposit_etb', 12, 2);
            $table->string('deposit_transaction_id')->nullable();
            $table->enum('deposit_status', ['unpaid','paid','refunded','forfeited'])->default('unpaid');
            $table->enum('quality_grade', ['A','B','C','any'])->default('any');
            $table->string('delivery_region_id')->nullable();
            $table->date('delivery_start_date');
            $table->date('delivery_end_date');
            $table->enum('status', ['draft','active','partially_fulfilled','fulfilled','cancelled','expired'])->default('draft');
            $table->decimal('fulfilled_kg', 12, 3)->default('0.000');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('buyer_id');
            $table->index('commodity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forward_contracts');
    }
};
