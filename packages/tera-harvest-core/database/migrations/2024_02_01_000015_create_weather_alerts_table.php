<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weather_alerts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->enum('alert_type', ['heavy_rain','drought','flood','frost','hail','strong_wind','extreme_heat'])->default('heavy_rain');
            $table->enum('severity', ['watch','warning','emergency'])->default('watch');
            $table->string('region_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('max_wind_speed_kmh', 8, 2)->nullable();
            $table->decimal('precipitation_mm', 8, 2)->nullable();
            $table->decimal('min_temp_c', 5, 2)->nullable();
            $table->decimal('max_temp_c', 5, 2)->nullable();
            $table->json('raw_forecast')->nullable();
            $table->integer('shipments_at_risk')->default(0);
            $table->integer('shipments_rerouted')->default(0);
            $table->integer('farmers_notified')->default(0);
            $table->timestamp('forecast_valid_from');
            $table->timestamp('forecast_valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'is_active']);
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_alerts');
    }
};
