<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ngo_impact_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('programme_id');
            $table->string('company_id');
            $table->string('snapshot_period');
            $table->integer('active_beneficiaries')->default(0);
            $table->integer('graduated_beneficiaries')->default(0);
            $table->decimal('avg_income_change_pct', 6, 2)->default('0.00');
            $table->decimal('avg_yield_change_pct', 6, 2)->default('0.00');
            $table->decimal('total_transactions_etb', 14, 2)->default('0.00');
            $table->decimal('total_kg_traded', 14, 3)->default('0.000');
            $table->integer('women_beneficiaries')->default(0);
            $table->integer('youth_beneficiaries')->default(0);
            $table->json('kpi_values')->nullable();
            $table->string('generated_by')->nullable();
            $table->string('report_url')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['programme_id', 'snapshot_period']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngo_impact_snapshots');
    }
};
