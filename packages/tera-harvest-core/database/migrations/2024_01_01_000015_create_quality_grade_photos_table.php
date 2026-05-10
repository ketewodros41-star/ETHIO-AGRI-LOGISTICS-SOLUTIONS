<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quality_grade_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('grade_id');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('url');
            $table->string('caption')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('grade_id')->references('id')->on('quality_grades')->cascadeOnDelete();
            $table->index('grade_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_grade_photos');
    }
};
