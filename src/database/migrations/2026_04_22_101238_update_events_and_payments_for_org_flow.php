<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE events DROP CONSTRAINT events_status_check");
        DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check 
        CHECK (status IN ('pending','upcoming','ongoing','completed','cancelled'))");


        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->nullable()
                ->after('donation_request_id')
                ->constrained('events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE events DROP CONSTRAINT events_status_check");
        DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check 
        CHECK (status IN ('upcoming','ongoing','completed','cancelled'))");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};
