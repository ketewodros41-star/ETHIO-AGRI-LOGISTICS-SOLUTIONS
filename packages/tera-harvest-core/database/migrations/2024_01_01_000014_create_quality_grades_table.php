<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quality_grades', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('shipment_id')->nullable();
            $table->string('listing_id')->nullable();
            $table->string('inspector_id')->nullable();
            $table->string('crop_type');
            $table->enum('grading_standard', ['ecx','fao','custom'])->default('ecx');
            $table->enum('overall_grade', ['A','B','C','rejected']);
            $table->decimal('weight_kg', 10, 3);
            $table->decimal('moisture_pct', 5, 2)->nullable();
            $table->decimal('foreign_matter_pct', 5, 2)->nullable();
            $table->decimal('defect_pct', 5, 2)->nullable();
            $table->tinyInteger('colour_score')->unsigned()->nullable();  // 1-5
            $table->tinyInteger('smell_score')->unsigned()->nullable();   // 1-5
            $table->json('custom_attributes')->nullable();
            $table->text('notes')->nullable();
            $table->string('certificate_number')->unique()->nullable();
            $table->string('certificate_pdf_url')->nullable();
            $table->string('certificate_hash', 64)->nullable();
            $table->enum('status', ['draft','issued','disputed','revoked'])->default('draft');
            $table->timestamp('graded_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('shipment_id');
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_grades');
    }
};
