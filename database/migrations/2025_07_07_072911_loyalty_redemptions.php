<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->cascadeOnDelete();
            // $table->foreignId('reward_id')->references('id')->on('loyalty_rewards')->onDelete('cascade');

            $table->unsignedInteger('points_used');
            $table->string('status')->default('pending'); 
            $table->string('code')->nullable()->unique(); 
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'reward_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('loyalty_redemptions');
    }
};