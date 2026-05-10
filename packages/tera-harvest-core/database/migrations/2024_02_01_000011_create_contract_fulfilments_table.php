<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_fulfilments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('contract_id');
            $table->string('lot_id')->nullable();
            $table->string('listing_id')->nullable();
            $table->string('company_id');
            $table->decimal('quantity_kg', 12, 3);
            $table->enum('quality_grade', ['A','B','C'])->default('C');
            $table->decimal('price_per_kg_etb', 10, 2);
            $table->decimal('total_etb', 12, 2);
            $table->enum('status', ['pending','accepted','rejected','delivered'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_fulfilments');
    }
};
