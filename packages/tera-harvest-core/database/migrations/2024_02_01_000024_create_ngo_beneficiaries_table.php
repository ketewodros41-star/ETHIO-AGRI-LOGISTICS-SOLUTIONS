<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ngo_beneficiaries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('programme_id');
            $table->string('farmer_id');
            $table->string('company_id');
            $table->enum('status', ['enrolled','active','graduated','dropped_out','suspended'])->default('enrolled');
            $table->json('baseline_data')->nullable();
            $table->json('current_data')->nullable();
            $table->decimal('income_baseline_etb', 12, 2)->nullable();
            $table->decimal('income_current_etb', 12, 2)->nullable();
            $table->decimal('yield_baseline_kg', 12, 3)->nullable();
            $table->decimal('yield_current_kg', 12, 3)->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('graduated_at')->nullable();
            $table->timestamps();

            $table->unique(['programme_id', 'farmer_id']);
            $table->index(['programme_id', 'status']);
            $table->index('farmer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngo_beneficiaries');
    }
};
