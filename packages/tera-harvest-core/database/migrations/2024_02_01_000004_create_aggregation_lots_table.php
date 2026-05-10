<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aggregation_lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('lot_number')->unique();
            $table->string('commodity');
            $table->string('collection_point_id')->nullable();
            $table->decimal('target_kg', 12, 3);
            $table->decimal('collected_kg', 12, 3)->default('0.000');
            $table->enum('quality_grade', ['A','B','C','mixed'])->default('mixed');
            $table->enum('status', ['open','closed','dispatched','sold','settled'])->default('open');
            $table->string('buyer_id')->nullable();
            $table->decimal('agreed_price_per_kg_etb', 10, 2)->nullable();
            $table->decimal('total_sale_etb', 12, 2)->nullable();
            $table->decimal('platform_commission_etb', 12, 2)->nullable();
            $table->decimal('net_farmer_payout_etb', 12, 2)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('commodity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregation_lots');
    }
};
