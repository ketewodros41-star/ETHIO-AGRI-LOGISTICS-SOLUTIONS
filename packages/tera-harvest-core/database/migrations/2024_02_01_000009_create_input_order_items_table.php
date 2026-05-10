<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('input_order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('order_id');
            $table->string('product_id');
            $table->string('company_id');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('unit')->default('kg');
            $table->decimal('unit_price_etb', 10, 2);
            $table->decimal('line_total_etb', 12, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_order_items');
    }
};
