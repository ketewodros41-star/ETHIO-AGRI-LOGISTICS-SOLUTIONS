<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ethiopia_kebeles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('woreda_id');
            $table->string('name_en');
            $table->string('name_am');
            $table->timestamps();

            $table->foreign('woreda_id')->references('id')->on('ethiopia_woredas')->cascadeOnDelete();
            $table->index('woreda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopia_kebeles');
    }
};
