<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('microfinance_credit_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('farmer_id');
            $table->string('company_id');
            $table->string('partner_name');
            $table->string('partner_reference')->nullable();
            $table->decimal('requested_amount_etb', 12, 2);
            $table->tinyInteger('score_at_request')->unsigned()->default(0);
            $table->enum('score_band_at_request', ['unrated','bronze','silver','gold','platinum'])->default('unrated');
            $table->json('data_package')->nullable();
            $table->enum('status', ['pending','approved','declined','disbursed','repaid','defaulted'])->default('pending');
            $table->decimal('approved_amount_etb', 12, 2)->nullable();
            $table->decimal('interest_rate_pct', 5, 2)->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('repaid_at')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'company_id']);
            $table->index(['company_id', 'status']);
            $table->index('partner_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microfinance_credit_requests');
    }
};
