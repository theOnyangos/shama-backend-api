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
        Schema::create('team_locations', function (Blueprint $table) {
            $table->id();
            $table->string('team_name');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('location');
            $table->unsignedBigInteger('created_by'); // Assuming this is a user_id from a users table
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_locations');
    }
};
