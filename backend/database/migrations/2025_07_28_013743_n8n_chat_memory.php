<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('n8n_chat_memory', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });
    }
 
    public function down()
    {
        Schema::dropIfExists('n8n_chat_memory');
    }

};
