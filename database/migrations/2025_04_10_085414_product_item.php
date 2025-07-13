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
        Schema::create('product_items', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to product_categories
            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            // Basic product information
            $table->string('item_name', 255);
            $table->string('sku', 100)->unique(); // Stock Keeping Unit
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // Pricing and stock
            $table->decimal('price', 10, 2)->default(0.00);
            $table->unsignedInteger('stock_quantity')->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
