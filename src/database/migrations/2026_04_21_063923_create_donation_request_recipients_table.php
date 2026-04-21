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
        Schema::create('donation_request_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('donation_requests')->onDelete('cascade');
            $table->foreignId('donor_profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->enum('response_status', ['pending', 'accepted', 'rejected', 'donated'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('note')->nullable();
            $table->unique(['request_id', 'donor_profile_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_request_recipients');
    }
};
