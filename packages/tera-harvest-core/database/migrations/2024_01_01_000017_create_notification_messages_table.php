<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('recipient_id')->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->enum('channel', ['sms','ussd','push','email','websocket']);
            $table->enum('language', ['am','en'])->default('am');
            $table->string('event_type');
            $table->text('message_am')->nullable();
            $table->text('message_en')->nullable();
            $table->text('sent_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->enum('status', ['queued','sent','delivered','failed'])->default('queued');
            $table->enum('provider', ['africas_talking','firebase','mail','websocket']);
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('recipient_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_messages');
    }
};
