<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Append-only immutable event ledger — no updated_at, no soft deletes
        Schema::create('payment_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('transaction_id');
            $table->string('event_type');
            $table->json('payload');
            $table->string('hash', 64);       // SHA-256 of previous_hash + payload
            $table->string('previous_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — append-only

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
