<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('input_suppliers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->enum('type', ['seed','fertilizer','pesticide','equipment','mixed'])->default('mixed');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('region_id')->nullable();
            $table->string('woreda_id')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->default('0.00');
            $table->unsignedInteger('total_orders_fulfilled')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_suppliers');
    }
};
