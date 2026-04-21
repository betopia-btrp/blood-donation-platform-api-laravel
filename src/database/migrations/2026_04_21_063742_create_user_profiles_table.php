<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->enum('blood_group', ['A+','A-','B+','B-','O+','O-','AB+','AB-'])->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('area', 150)->nullable();
            $table->boolean('is_available')->default(true);
            $table->date('last_donation_date')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
