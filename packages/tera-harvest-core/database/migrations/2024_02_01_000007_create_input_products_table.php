<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('input_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('supplier_id');
            $table->string('company_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->enum('category', ['seed','fertilizer','pesticide','herbicide','fungicide','equipment','other'])->default('other');
            $table->text('description')->nullable();
            $table->string('unit')->default('kg');
            $table->decimal('price_per_unit_etb', 10, 2);
            $table->decimal('stock_quantity', 12, 3)->default('0.000');
            $table->decimal('reorder_threshold', 12, 3)->default('0.000');
            $table->boolean('is_active')->default(true);
            $table->json('certifications')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'company_id']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_products');
    }
};
