<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_score_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('farmer_id');
            $table->string('company_id');
            $table->tinyInteger('score')->unsigned()->default(0);
            $table->enum('score_band', ['unrated','bronze','silver','gold','platinum'])->default('unrated');
            $table->tinyInteger('delta')->default(0);
            $table->text('reason')->nullable();
            $table->string('triggered_by_event')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['farmer_id', 'company_id']);
            $table->index(['company_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_score_history');
    }
};
