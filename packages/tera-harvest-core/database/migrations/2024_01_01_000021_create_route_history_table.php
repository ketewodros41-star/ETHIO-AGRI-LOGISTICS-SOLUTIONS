<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('order_id');
            $table->string('driver_id');
            $table->json('planned_segments')->nullable();
            $table->json('actual_path')->nullable();          // GPS trace
            $table->decimal('planned_hours', 5, 2)->nullable();
            $table->decimal('actual_hours', 5, 2)->nullable();
            $table->decimal('fuel_litres_used', 8, 2)->nullable();
            $table->json('incidents')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_history');
    }
};
