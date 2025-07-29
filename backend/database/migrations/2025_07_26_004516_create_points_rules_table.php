<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointsRulesTable extends Migration
{
    public function up()
    {
        Schema::create('points_rules', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('parameters')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->nullableMorphs('associated_entity'); // For merchant or product
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('points_rules');
    }
}
