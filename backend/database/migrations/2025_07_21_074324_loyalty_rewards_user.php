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
        Schema::create('loyalty_rewards_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_reward_id')
                ->constrained('loyalty_rewards')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->decimal('points_used', 10, 2)->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->enum('status', ['pending', 'redeemed', 'expired'])
                ->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
