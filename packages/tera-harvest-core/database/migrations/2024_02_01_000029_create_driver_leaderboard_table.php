<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_leaderboard', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('period_type')->default('weekly');
            $table->string('period_label');
            $table->string('region_id')->nullable();
            $table->integer('rank');
            $table->string('driver_id');
            $table->string('display_name');
            $table->integer('deliveries_completed')->default(0);
            $table->decimal('total_distance_km', 12, 3)->default('0.000');
            $table->decimal('net_earnings_etb', 12, 2)->default('0.00');
            $table->decimal('avg_rating', 3, 2)->default('0.00');
            $table->decimal('on_time_rate_pct', 5, 2)->default('0.00');
            $table->json('badges')->nullable();
            $table->integer('score')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['company_id', 'period_type', 'period_label', 'rank', 'region_id']);
            $table->index(['company_id', 'period_type', 'period_label']);
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_leaderboard');
    }
};
