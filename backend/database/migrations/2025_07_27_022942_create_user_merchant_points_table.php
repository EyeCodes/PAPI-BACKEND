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
        Schema::create('user_merchant_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->integer('total_earned')->default(0);
            $table->integer('total_spent')->default(0);
            $table->timestamp('last_earned_at')->nullable();
            $table->timestamp('last_spent_at')->nullable();
            $table->timestamps();

            // Ensure unique user-merchant combination
            $table->unique(['user_id', 'merchant_id']);

            // Indexes for performance
            $table->index(['user_id', 'points']);
            $table->index(['merchant_id', 'points']);
            $table->index(['user_id', 'merchant_id', 'points']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_merchant_points');
    }
};
