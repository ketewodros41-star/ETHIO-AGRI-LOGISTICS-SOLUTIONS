<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ethiopia_regions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name_en');
            $table->string('name_am');
            $table->string('code', 10)->unique();
            $table->geometry('geometry', 'polygon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopia_regions');
    }
};
