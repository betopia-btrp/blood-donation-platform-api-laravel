<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old constraint
        DB::statement("ALTER TABLE reports DROP CONSTRAINT reports_report_type_check");
        
        // Add the new constraint including 'other'
        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_report_type_check 
        CHECK (report_type::text IN ('spam', 'fake', 'abusive', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE reports DROP CONSTRAINT reports_report_type_check");
        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_report_type_check 
        CHECK (report_type::text IN ('spam', 'fake', 'abusive'))");
    }
};