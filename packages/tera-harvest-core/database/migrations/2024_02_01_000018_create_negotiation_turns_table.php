<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('negotiation_turns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('negotiation_id');
            $table->string('company_id');
            $table->integer('turn_number');
            $table->enum('actor', ['buyer','seller','ai']);
            $table->decimal('offered_price_etb', 10, 2);
            $table->text('message')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('negotiation_id');
            $table->index(['negotiation_id', 'turn_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiation_turns');
    }
};
