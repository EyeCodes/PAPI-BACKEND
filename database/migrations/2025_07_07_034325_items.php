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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            
            $table->unsignedBigInteger('category_id')->nullable()->comment('For category-based loyalty rules');
            $table->boolean('is_eligible_for_points')->default(true);
            $table->boolean('can_be_reward')->default(false);
            
            $table->text('description')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->integer('quantity')->default(0);
            $table->date('expiration_date')->nullable();
            $table->string('image_url')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};