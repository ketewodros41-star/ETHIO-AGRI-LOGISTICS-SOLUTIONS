<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_earnings_summary', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('driver_id');
            $table->string('company_id');
            $table->string('period_type')->default('weekly');
            $table->string('period_label');
            $table->integer('year');
            $table->integer('week')->nullable();
            $table->integer('month')->nullable();
            $table->integer('deliveries_completed')->default(0);
            $table->decimal('total_distance_km', 12, 3)->default('0.000');
            $table->decimal('base_earnings_etb', 12, 2)->default('0.00');
            $table->decimal('bonus_etb', 12, 2)->default('0.00');
            $table->decimal('deductions_etb', 12, 2)->default('0.00');
            $table->decimal('net_earnings_etb', 12, 2)->default('0.00');
            $table->decimal('avg_rating', 3, 2)->default('0.00');
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->json('badges_earned')->nullable();
            $table->boolean('disbursed')->default(false);
            $table->timestamp('disbursed_at')->nullable();
            $table->string('disbursement_transaction_id')->nullable();
            $table->timestamps();

            $table->unique(['driver_id', 'period_type', 'period_label']);
            $table->index(['company_id', 'period_type', 'period_label']);
            $table->index(['company_id', 'disbursed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_earnings_summary');
    }
};
