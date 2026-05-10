<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('wallet_id');
            $table->string('order_id')->nullable();
            $table->enum('type', [
                'deposit','withdrawal','escrow_hold','escrow_release',
                'commission','refund','fee',
            ]);
            $table->decimal('amount_etb', 12, 2);
            $table->decimal('fee_etb', 12, 2)->default('0.00');
            $table->enum('provider', ['chapa','telebirr','cbe_birr','internal']);
            $table->string('provider_reference')->nullable();
            $table->json('provider_payload')->nullable();
            $table->enum('status', ['pending','processing','completed','failed','reversed'])->default('pending');
            $table->string('initiated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('payment_wallets')->cascadeOnDelete();
            $table->index('wallet_id');
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
