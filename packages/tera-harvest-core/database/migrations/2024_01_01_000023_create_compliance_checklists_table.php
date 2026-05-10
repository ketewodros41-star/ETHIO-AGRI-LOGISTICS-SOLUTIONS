<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_checklists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('order_id');
            $table->boolean('generated_by_agent')->default(false);
            $table->json('items')->nullable();
            $table->enum('overall_status', ['complete','incomplete','flagged'])->default('incomplete');
            $table->json('flagged_items')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_checklists');
    }
};
