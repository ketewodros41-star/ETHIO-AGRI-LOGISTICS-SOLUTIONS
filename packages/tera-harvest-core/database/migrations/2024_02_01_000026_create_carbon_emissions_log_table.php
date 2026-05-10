<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carbon_emissions_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('order_id')->nullable();
            $table->string('driver_id')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->enum('activity_type', ['road_transport','air_transport','cold_storage','processing','packaging'])->default('road_transport');
            $table->string('fuel_type')->default('diesel');
            $table->decimal('distance_km', 10, 3)->default('0.000');
            $table->decimal('fuel_consumed_litres', 10, 3)->nullable();
            $table->decimal('cargo_weight_kg', 12, 3)->default('0.000');
            $table->decimal('emission_factor_kg_co2_per_km', 8, 5)->default('0.00000');
            $table->decimal('co2_kg', 10, 3)->default('0.000');
            $table->decimal('co2e_kg', 10, 3)->default('0.000');
            $table->string('ipcc_source_version')->default('2006');
            $table->timestamp('activity_date');
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id');
            $table->index(['company_id', 'activity_date']);
            $table->index('order_id');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carbon_emissions_log');
    }
};
