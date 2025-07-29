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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Basic Product Info
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            // Price & Stock
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency')->default('PHP');
            $table->integer('stock')->nullable();

            // API Integration Fields
            $table->string('external_id')->nullable()->index(); // External API product ID
            $table->string('source')->nullable();               // API source/provider
            $table->timestamp('last_synced_at')->nullable();    // Last sync time

            // Relation to Merchant
            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete(); // Product tied to a merchant
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
