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
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('monthly_payment', 12, 2)->nullable();
            $table->string('type'); // credit_card, loan, mortgage, etc.
            $table->string('currency')->default('PHP');
            $table->date('due_date')->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable(); // Annual interest rate
            $table->string('status')->default('active'); // active, paid, defaulted
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};
