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
        Schema::create('team_location_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_location_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['coach'])->default('coach');
            $table->timestamps();

            $table->foreign('team_location_id')->references('id')->on('team_locations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_location_user');
    }
};
