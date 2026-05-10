<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('uuid')->unique();
            $table->string('company_id');
            $table->string('order_id');
            $table->enum('document_type', [
                'phytosanitary','certificate_of_origin','ecx_grade',
                'customs_declaration','bill_of_lading','packing_list',
                'inspection_certificate',
            ]);
            $table->string('document_number')->unique();
            $table->string('file_url')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('chain_hash', 64)->nullable();
            $table->enum('status', ['draft','issued','submitted','accepted','rejected','expired'])->default('draft');
            $table->string('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('authority')->nullable();
            $table->string('authority_reference')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('order_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_documents');
    }
};
