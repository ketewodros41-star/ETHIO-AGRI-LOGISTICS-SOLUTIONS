<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('farm_plots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('farmer_id');
            $table->string('company_id');
            $table->string('plot_name');
            $table->decimal('area_ha', 10, 4);
            $table->string('region_id')->nullable();
            $table->string('woreda_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('polygon_coordinates')->nullable();
            $table->enum('soil_type', ['clay','sandy','loam','silt','peaty','chalky','mixed','unknown'])->default('unknown');
            $table->enum('irrigation_type', ['rainfed','irrigated','partially_irrigated'])->default('rainfed');
            $table->decimal('elevation_m', 8, 2)->nullable();
            $table->json('current_crops')->nullable();
            $table->json('crop_history')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farmer_id', 'company_id']);
            $table->index('company_id');
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_plots');
    }
};
