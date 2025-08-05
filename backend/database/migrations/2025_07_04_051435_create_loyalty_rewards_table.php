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
            // Primary key
            $table->id();


            $table->foreignId('loyalty_program_rule_id')
                ->nullable()
                ->constrained('loyalty_program_rules')
                ->nullOnDelete();

            // Reward details
            $table->string('reward_name', 255);
            $table->text('description')->nullable();
            $table->string('reward_type', 50);

            // Point cost and discount
            $table->decimal('point_cost', 10, 2);
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();

            // Item/voucher
            // $table->foreignId('item_id')
            //     ->nullable()
            //     ->constrained('items')
            //     ->nullOnDelete();
            
            $table->string('voucher_code', 100)->nullable();

            // Status and limits
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_redemption_rate')->nullable();
            $table->unsignedInteger('expiration_days')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['reward_type', 'is_active']);
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
