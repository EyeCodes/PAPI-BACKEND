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
            $table->string('merchant');
            $table->decimal('amount', 10, 2);
            $table->date('purchase_date');
            $table->string('category')->default('Uncategorized');
            $table->text('description')->nullable();
            $table->json('items')->nullable(); // Array of purchased items
            $table->timestamps();

            $table->index(['user_id', 'purchase_date']);
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'merchant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
