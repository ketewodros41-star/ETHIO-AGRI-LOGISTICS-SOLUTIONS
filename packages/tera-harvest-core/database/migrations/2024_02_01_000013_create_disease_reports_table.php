<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disease_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('reported_by');
            $table->string('crop_type');
            $table->string('disease_name')->nullable();
            $table->enum('severity', ['low','medium','high','critical'])->default('low');
            $table->text('symptoms')->nullable();
            $table->json('photo_urls')->nullable();
            $table->string('region_id')->nullable();
            $table->string('woreda_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('affected_area_ha', 10, 2)->nullable();
            $table->json('ai_diagnosis')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('recommended_treatment')->nullable();
            $table->enum('status', ['pending','verified','treated','resolved','false_alarm'])->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'severity']);
            $table->index(['company_id', 'status']);
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_reports');
    }
};
