<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yield_predictions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('farmer_id');
            $table->string('company_id');
            $table->string('farm_plot_id')->nullable();
            $table->string('crop_type');
            $table->string('season');
            $table->decimal('planted_area_ha', 10, 4);
            $table->decimal('predicted_yield_kg', 12, 3)->nullable();
            $table->decimal('predicted_yield_min_kg', 12, 3)->nullable();
            $table->decimal('predicted_yield_max_kg', 12, 3)->nullable();
            $table->decimal('actual_yield_kg', 12, 3)->nullable();
            $table->decimal('accuracy_pct', 5, 2)->nullable();
            $table->json('model_inputs')->nullable();
            $table->json('ai_reasoning')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->date('harvest_expected_start')->nullable();
            $table->date('harvest_expected_end')->nullable();
            $table->date('harvest_actual_date')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'company_id']);
            $table->index(['company_id', 'crop_type', 'season']);
            $table->index('farm_plot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yield_predictions');
    }
};
