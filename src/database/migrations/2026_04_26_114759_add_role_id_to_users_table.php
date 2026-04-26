<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')
                ->constrained('roles')->onDelete('restrict');
        });

        $roles = DB::table('roles')->pluck('id', 'name');

        DB::table('users')->get()->each(function ($user) use ($roles) {
            DB::table('users')->where('id', $user->id)->update([
                'role_id' => $roles[$user->role] ?? $roles['user'],
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('user');
        });

        DB::table('users')->get()->each(function ($user) {
            $role = DB::table('roles')->where('id', $user->role->name_id)->first();
            DB::table('users')->where('id', $user->id)->update([
                'role' => $role?->name ?? 'user',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
