<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('government_data_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('api_key_id');
            $table->string('company_id');
            $table->string('endpoint');
            $table->json('query_params')->nullable();
            $table->integer('response_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('records_returned')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('api_key_id');
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_data_requests');
    }
};
