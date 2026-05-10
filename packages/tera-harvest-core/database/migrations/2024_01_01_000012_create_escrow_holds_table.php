<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('escrow_holds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('order_id');
            $table->string('buyer_wallet_id');
            $table->string('seller_wallet_id');
            $table->decimal('driver_commission_etb', 10, 2)->default('0.00');
            $table->decimal('broker_commission_etb', 10, 2)->default('0.00');
            $table->decimal('amount_etb', 12, 2);
            $table->enum('status', ['held','released','refunded','disputed'])->default('held');
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->enum('release_trigger', ['delivery_confirmed','quality_approved','manual_admin'])->nullable();
            $table->string('released_by')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_holds');
    }
};
