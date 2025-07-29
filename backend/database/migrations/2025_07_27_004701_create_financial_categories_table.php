<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // For UI display
            $table->string('color')->nullable(); // For UI display
            $table->boolean('is_default')->default(false); // Predefined categories
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('ai_keywords')->nullable(); // Keywords for AI categorization
            $table->timestamps();

            $table->index(['is_default', 'is_active']);
            $table->index(['sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
