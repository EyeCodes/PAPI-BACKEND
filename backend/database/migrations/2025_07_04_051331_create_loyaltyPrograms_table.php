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
        Schema::create('loyalty_programs', function (Blueprint $table) {
            // Primary key (auto-increment)
            $table->id();

            // Foreign key to companies table
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Program details
            $table->string('program_name', 255);
            $table->text('description')->nullable();
            $table->string('program_type', 100)->default('point_based');

            // Status & dates
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('instructions')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['program_name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
