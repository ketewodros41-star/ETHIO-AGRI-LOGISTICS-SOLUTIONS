<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_negotiations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('listing_id');
            $table->string('buyer_id');
            $table->string('seller_id');
            $table->decimal('initial_ask_etb', 10, 2);
            $table->decimal('current_offer_etb', 10, 2);
            $table->decimal('agreed_price_etb', 10, 2)->nullable();
            $table->decimal('quantity_kg', 12, 3);
            $table->enum('status', ['active','accepted','rejected','expired','converted_to_order'])->default('active');
            $table->string('order_id')->nullable();
            $table->integer('max_turns')->default(5);
            $table->integer('current_turn')->default(0);
            $table->json('ai_suggestion')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'buyer_id']);
            $table->index(['company_id', 'status']);
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_negotiations');
    }
};
