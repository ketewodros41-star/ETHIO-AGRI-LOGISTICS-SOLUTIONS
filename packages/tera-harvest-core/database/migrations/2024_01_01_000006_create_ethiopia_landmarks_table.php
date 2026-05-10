<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ethiopia_landmarks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('kebele_id')->nullable();
            $table->string('name_en');
            $table->string('name_am');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('photo_url')->nullable();
            $table->unsignedInteger('confirmed_count')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('kebele_id')->references('id')->on('ethiopia_kebeles')->nullOnDelete();
            $table->index(['latitude', 'longitude']);
            $table->index('kebele_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopia_landmarks');
    }
};
