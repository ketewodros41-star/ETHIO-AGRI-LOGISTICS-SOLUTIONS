<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('harvest_listing_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('listing_id');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('url');
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('harvest_listings')->cascadeOnDelete();
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_listing_photos');
    }
};
