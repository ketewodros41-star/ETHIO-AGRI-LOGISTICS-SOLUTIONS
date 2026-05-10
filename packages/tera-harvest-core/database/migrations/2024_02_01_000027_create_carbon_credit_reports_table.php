<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carbon_credit_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('report_period');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_co2e_kg', 14, 3)->default('0.000');
            $table->decimal('total_distance_km', 14, 3)->default('0.000');
            $table->integer('total_orders')->default(0);
            $table->decimal('co2e_per_order_kg', 10, 3)->default('0.000');
            $table->decimal('co2e_per_km_kg', 10, 5)->default('0.00000');
            $table->decimal('industry_benchmark_kg', 14, 3)->nullable();
            $table->decimal('variance_pct', 6, 2)->nullable();
            $table->json('breakdown_by_fuel_type')->nullable();
            $table->json('breakdown_by_region')->nullable();
            $table->string('report_url')->nullable();
            $table->string('document_hash')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carbon_credit_reports');
    }
};
