<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('harvest_listing_views', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('listing_id');
            $table->string('viewer_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('listing_id')->references('id')->on('harvest_listings')->cascadeOnDelete();
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_listing_views');
    }
};
