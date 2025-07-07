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
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('program_id');
            $table->integer('points_balance');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('program_id')->references('id')->on('loyaltyPrograms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};
