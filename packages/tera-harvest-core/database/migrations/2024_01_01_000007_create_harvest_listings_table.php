<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('harvest_listings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('farmer_id')->nullable();
            $table->string('cooperative_id')->nullable();
            $table->enum('crop_type', [
                'teff','coffee','sesame','chickpeas','wheat',
                'sorghum','maize','vegetables','fruits','pulses','spices','other',
            ]);
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('asking_price_etb', 10, 2);
            $table->decimal('ai_suggested_price_etb', 10, 2)->nullable();
            $table->enum('quality_grade', ['A','B','C','ungraded'])->default('ungraded');
            $table->date('harvest_date')->nullable();
            $table->date('availability_from')->nullable();
            $table->date('availability_until')->nullable();
            $table->string('woreda_id')->nullable();
            $table->string('kebele_id')->nullable();
            $table->string('landmark_id')->nullable();
            $table->enum('storage_type', ['open_air','warehouse','cold_storage'])->default('open_air');
            $table->enum('status', ['draft','active','matched','sold','expired','cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('crop_type');
            $table->index('status');
            $table->index('woreda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_listings');
    }
};
