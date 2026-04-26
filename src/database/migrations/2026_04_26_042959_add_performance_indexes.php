<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['is_active', 'role']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->index(['is_available', 'trust_score']);
            $table->index('blood_group');
            $table->index('district');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'event_date']);
            $table->index('district');
        });

        Schema::table('donation_request_recipients', function (Blueprint $table) {
            $table->index(['request_id', 'response_status']);
            $table->index(['donor_profile_id', 'response_status']);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->index(['event_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn($t) => $t->dropIndex(['is_active', 'role']));
        Schema::table('user_profiles', function ($t) {
            $t->dropIndex(['is_available', 'trust_score']);
            $t->dropIndex(['blood_group']);
            $t->dropIndex(['district']);
        });
        Schema::table('events', function ($t) {
            $t->dropIndex(['status', 'event_date']);
            $t->dropIndex(['district']);
        });
        Schema::table('donation_request_recipients', function ($t) {
            $t->dropIndex(['request_id', 'response_status']);
            $t->dropIndex(['donor_profile_id', 'response_status']);
        });
        Schema::table('event_registrations', fn($t) => $t->dropIndex(['event_id', 'profile_id']));
    }
};
