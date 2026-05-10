<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_wallets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('owner_id');
            $table->string('owner_type');
            $table->decimal('balance_etb', 12, 2)->default('0.00');
            $table->decimal('reserved_etb', 12, 2)->default('0.00');
            $table->string('currency', 3)->default('ETB');
            $table->enum('status', ['active','frozen','closed'])->default('active');
            $table->timestamps();

            $table->index('company_id');
            $table->index(['owner_id', 'owner_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_wallets');
    }
};
