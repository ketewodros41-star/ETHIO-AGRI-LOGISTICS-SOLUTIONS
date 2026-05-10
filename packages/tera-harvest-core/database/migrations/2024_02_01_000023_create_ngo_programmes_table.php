<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ngo_programmes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('ngo_name');
            $table->string('programme_name');
            $table->string('programme_code')->unique();
            $table->text('description')->nullable();
            $table->enum('focus_area', ['food_security','market_access','climate_resilience','women_empowerment','youth_agri','nutrition','other'])->default('other');
            $table->string('region_id')->nullable();
            $table->decimal('budget_usd', 14, 2)->nullable();
            $table->decimal('budget_etb', 14, 2)->nullable();
            $table->integer('target_beneficiaries')->default(0);
            $table->integer('enrolled_beneficiaries')->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning','active','completed','suspended'])->default('planning');
            $table->json('kpis')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('focus_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngo_programmes');
    }
};
