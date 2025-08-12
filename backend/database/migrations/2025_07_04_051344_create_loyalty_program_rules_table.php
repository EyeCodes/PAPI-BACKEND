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
        Schema::create('loyalty_program_rules', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to loyalty_programs
            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->onDelete('cascade');

            // Rule details
            $table->string('rule_name', 255);
            $table->enum('rule_type', ['purchase_based', 'referral_bonus', 'birthday'])
                ->default('purchase_based');

            $table->unsignedBigInteger('points_earned')->default(0);
            $table->decimal('amount_per_point', 10, 2)->nullable();
            $table->decimal('min_purchase_amount', 10, 2)->nullable();

            // Optional product constraints
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_item_id')->nullable()->constrained('product_items')->nullOnDelete();

            // Status and limits
            $table->boolean('is_active')->default(true);
            $table->date('active_from_date')->nullable();
            $table->date('active_to_date')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_program_rules');
    }
};
