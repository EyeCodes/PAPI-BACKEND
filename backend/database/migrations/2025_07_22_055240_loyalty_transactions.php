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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->integer('points');
            $table->string('transaction_type'); // earn, redeemed, expired 
            $table->string('source'); // purchase, referral, birthday, etc
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('program_id')->references('id')->on('loyalty_programs');
            $table->foreign('item_id')->references('id')->on('product_items');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("point_transactions");
    }
};