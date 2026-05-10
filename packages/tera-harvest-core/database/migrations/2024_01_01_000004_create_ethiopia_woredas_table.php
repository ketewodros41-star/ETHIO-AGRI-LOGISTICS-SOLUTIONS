<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ethiopia_woredas', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('zone_id');
            $table->string('name_en');
            $table->string('name_am');
            $table->string('code', 10)->nullable();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('ethiopia_zones')->cascadeOnDelete();
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopia_woredas');
    }
};
