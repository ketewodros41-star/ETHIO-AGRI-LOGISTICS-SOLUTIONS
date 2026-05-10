<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disease_alerts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('source_report_id');
            $table->string('crop_type');
            $table->string('disease_name');
            $table->enum('severity', ['low','medium','high','critical'])->default('medium');
            $table->string('region_id')->nullable();
            $table->decimal('radius_km', 8, 2)->default('50.00');
            $table->text('alert_message')->nullable();
            $table->text('alert_message_am')->nullable();
            $table->integer('farmers_notified')->default(0);
            $table->enum('escalation_status', ['none','local','regional','national'])->default('none');
            $table->boolean('government_notified')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'severity']);
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_alerts');
    }
};
