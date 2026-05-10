<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_configurations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('company_id');
            $table->string('timezone')->default('Africa/Addis_Ababa');
            $table->string('currency', 3)->default('ETB');
            $table->string('default_language', 5)->default('am');
            $table->json('active_payment_providers')->nullable();
            $table->json('active_crop_types')->nullable();
            $table->enum('grading_standard', ['ecx', 'fao', 'custom'])->default('ecx');
            $table->json('custom_grade_config')->nullable();
            $table->enum('escrow_release_trigger', ['delivery_confirmed', 'quality_approved', 'manual'])->default('delivery_confirmed');
            $table->decimal('driver_commission_pct', 5, 2)->default(10.00);
            $table->decimal('broker_commission_pct', 5, 2)->default(2.00);
            $table->string('sms_sender_id', 11)->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_colour', 7)->nullable();
            $table->boolean('ecx_notifications_enabled')->default(true);
            $table->json('compliance_document_types')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
    }
};
