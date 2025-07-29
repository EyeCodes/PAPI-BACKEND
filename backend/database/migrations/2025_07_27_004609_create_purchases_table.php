<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('merchant_name')->nullable(); // For non-affiliated merchants
            $table->foreignId('merchant_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('financial_category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ai_categorized_category')->nullable(); // AI-suggested category
            $table->json('ai_analysis')->nullable(); // AI insights and analysis
            $table->json('metadata')->nullable(); // Additional data like location, payment method, etc.
            $table->date('purchase_date');
            $table->timestamps();

            $table->index(['user_id', 'purchase_date']);
            $table->index(['user_id', 'financial_category_id']);
            $table->index(['user_id', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
