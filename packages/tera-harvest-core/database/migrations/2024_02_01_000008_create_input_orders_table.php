<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('input_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('order_number')->unique();
            $table->string('farmer_id');
            $table->string('supplier_id');
            $table->string('company_id');
            $table->enum('status', ['pending','confirmed','dispatched','delivered','cancelled','refunded'])->default('pending');
            $table->decimal('subtotal_etb', 12, 2)->default('0.00');
            $table->decimal('delivery_fee_etb', 10, 2)->default('0.00');
            $table->decimal('total_etb', 12, 2)->default('0.00');
            $table->enum('payment_method', ['wallet','chapa','telebirr','cbe_birr','cash_on_delivery'])->default('wallet');
            $table->string('payment_transaction_id')->nullable();
            $table->enum('payment_status', ['unpaid','paid','refunded'])->default('unpaid');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_woreda_id')->nullable();
            $table->string('driver_id')->nullable();
            $table->timestamp('expected_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farmer_id', 'company_id']);
            $table->index(['company_id', 'status']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_orders');
    }
};
