<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_segments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('origin_woreda_id');
            $table->string('destination_woreda_id');
            $table->decimal('distance_km', 8, 2);
            $table->enum('road_type', ['paved','gravel','dirt','track'])->default('paved');
            $table->tinyInteger('condition_score')->unsigned()->default(5);  // 1-10
            $table->decimal('avg_transit_hours', 5, 2)->nullable();
            $table->boolean('truck_accessible')->default(true);
            $table->boolean('rainy_season_passable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index('origin_woreda_id');
            $table->index('destination_woreda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_segments');
    }
};
