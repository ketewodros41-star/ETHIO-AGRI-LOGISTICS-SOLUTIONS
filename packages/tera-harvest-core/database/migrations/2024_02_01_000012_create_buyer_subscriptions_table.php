<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('buyer_subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('buyer_id');
            $table->string('company_id');
            $table->string('commodity');
            $table->decimal('min_quantity_kg', 12, 3)->default('0.000');
            $table->decimal('max_price_per_kg_etb', 10, 2)->nullable();
            $table->enum('quality_grade', ['A','B','C','any'])->default('any');
            $table->string('preferred_region_id')->nullable();
            $table->enum('frequency', ['daily','weekly','fortnightly','monthly'])->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'company_id']);
            $table->index(['company_id', 'commodity']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_subscriptions');
    }
};
