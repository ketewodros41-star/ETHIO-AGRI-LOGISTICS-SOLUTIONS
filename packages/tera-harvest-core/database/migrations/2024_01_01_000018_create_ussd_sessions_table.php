<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('session_id')->unique();  // Africa's Talking session ID
            $table->string('phone_number', 20);
            $table->string('farmer_id')->nullable();
            $table->string('current_menu')->default('main');
            $table->json('session_data')->nullable();
            $table->enum('status', ['active','completed','timed_out'])->default('active');
            $table->timestamps();

            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
