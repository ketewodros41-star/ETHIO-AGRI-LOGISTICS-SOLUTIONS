<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('farmer_credit_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('farmer_id');
            $table->string('company_id');
            $table->tinyInteger('score')->unsigned()->default(0);
            $table->enum('score_band', ['unrated','bronze','silver','gold','platinum'])->default('unrated');
            $table->tinyInteger('delivery_reliability_score')->unsigned()->default(0);
            $table->tinyInteger('quality_consistency_score')->unsigned()->default(0);
            $table->tinyInteger('volume_history_score')->unsigned()->default(0);
            $table->tinyInteger('payment_behaviour_score')->unsigned()->default(0);
            $table->unsignedInteger('total_orders_completed')->default(0);
            $table->decimal('total_kg_traded', 12, 3)->default('0.000');
            $table->enum('avg_quality_grade', ['A','B','C','ungraded'])->default('ungraded');
            $table->tinyInteger('seasons_active')->unsigned()->default(0);
            $table->timestamp('last_dispute_at')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->tinyInteger('score_version')->unsigned()->default(1);
            $table->timestamps();

            $table->unique(['farmer_id', 'company_id']);
            $table->index('company_id');
            $table->index('score_band');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_credit_scores');
    }
};
