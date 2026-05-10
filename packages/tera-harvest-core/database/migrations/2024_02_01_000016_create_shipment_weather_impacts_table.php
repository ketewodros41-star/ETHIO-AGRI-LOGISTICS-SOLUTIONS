<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_weather_impacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('alert_id');
            $table->string('order_id');
            $table->string('company_id');
            $table->enum('action_taken', ['rerouted','delayed','cancelled','none'])->default('none');
            $table->string('original_route_id')->nullable();
            $table->string('new_route_id')->nullable();
            $table->integer('delay_hours')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index('alert_id');
            $table->index('order_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_weather_impacts');
    }
};
