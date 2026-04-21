<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            // $table->enum('document_type', ['trade_license', 'ngo_certificate', 'tax_certificate', 'other'])->nullable();
            // $table->string('document_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_documents');
    }
};
