<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('segment_id');
            $table->string('reported_by')->nullable();
            $table->enum('report_type', [
                'road_condition','hazard','closure','delay','new_route','congestion',
            ]);
            $table->text('description')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('severity', ['low','medium','high','impassable'])->default('low');
            $table->boolean('is_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->foreign('segment_id')->references('id')->on('route_segments')->cascadeOnDelete();
            $table->index('segment_id');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_reports');
    }
};
