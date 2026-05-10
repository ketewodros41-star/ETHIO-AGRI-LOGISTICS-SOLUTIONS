<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cold_chain_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('shipment_id');
            $table->string('sensor_id');
            $table->decimal('temperature_celsius', 5, 2);
            $table->decimal('humidity_pct', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('alert_triggered')->default(false);
            $table->timestamp('alert_acknowledged_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('shipment_id');
            $table->index('sensor_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_chain_logs');
    }
};
