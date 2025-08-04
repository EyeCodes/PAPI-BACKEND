<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'conversation', 'preference', 'insight', 'goal'
            $table->string('key')->nullable(); // For specific memory keys
            $table->text('content'); // The actual memory content
            $table->json('metadata')->nullable(); // Additional context
            $table->integer('importance')->default(1); // 1-10 scale for memory importance
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'key']);
            $table->index(['user_id', 'importance']);
            $table->index(['user_id', 'last_accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_memories');
    }
};
