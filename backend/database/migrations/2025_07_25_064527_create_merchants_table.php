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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->json('emails')->nullable();
            $table->json('phones')->nullable();
            $table->json('addresses')->nullable();
            $table->json('social_media')->nullable();
            $table->json('website')->nullable();
            $table->string('external_id')->unique()->nullable(); // External API identifier
            $table->string('source')->nullable();                // API source/provider
            $table->timestamp('last_synced_at')->nullable();     // Last sync time
            $table->boolean('is_active')->default(true);         // Soft deactivate
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
