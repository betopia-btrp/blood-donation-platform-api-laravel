<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_request_recipients', function (Blueprint $table) {
            $table->boolean('donor_confirmed')->default(false)->after('note');
            $table->boolean('requester_confirmed')->default(false)->after('donor_confirmed');
            $table->timestamp('donor_confirmed_at')->nullable()->after('requester_confirmed');
            $table->timestamp('requester_confirmed_at')->nullable()->after('donor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('donation_request_recipients', function (Blueprint $table) {
            $table->dropColumn(['donor_confirmed', 'requester_confirmed', 'donor_confirmed_at', 'requester_confirmed_at']);
        });
    }
};
