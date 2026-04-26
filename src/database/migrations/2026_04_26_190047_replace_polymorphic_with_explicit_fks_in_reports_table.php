<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['target_id', 'target_type']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('target_user_id')->nullable()->after('reporter_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('target_donation_request_id')->nullable()->after('target_user_id')->constrained('donation_requests')->nullOnDelete();
            $table->foreignId('target_event_id')->nullable()->after('target_donation_request_id')->constrained('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_user_id');
            $table->dropConstrainedForeignId('target_donation_request_id');
            $table->dropConstrainedForeignId('target_event_id');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('target_id')->after('reporter_user_id');
            $table->enum('target_type', ['user', 'donation_request', 'event'])->after('target_id');
        });
    }
};
