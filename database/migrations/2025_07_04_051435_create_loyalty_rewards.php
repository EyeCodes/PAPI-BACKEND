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
       Schema::create('loyalty_rewards', function (Blueprint $table) {  
            $table->id();
            $table->unsignedBigInteger('loyalty_program_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('loyalty_program_id')->references('id')->on('loyaltyPrograms')->onDelete('cascade');
            // $table->foreignId('loyalty_program_id')->constrained('loyalty_')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');



            $table->string('reward_name');
            $table->text('description')->nullable();
            $table->string('reward_type');
            $table->decimal('point_cost');
            $table->decimal('discount_value')->nullable();  
            $table->decimal('discount_percentage')->nullable();  
            $table->unsignedBigInteger('item_id');
            $table->string('voucher_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_redemption_rate')->nullable();
            $table->integer('expiration_days')->nullable();
            $table->timestamps();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
