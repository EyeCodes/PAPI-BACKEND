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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('display_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('business_type');

            $table->string('telephone_contact_1');
            $table->string('telephone_contact_2')->nullable();
            $table->string('email_contact_1');
            $table->string('email_contact_2')->nullable();

            $table->string('barangay');
            $table->string('city_municipality');
            $table->string('province');
            $table->string('region');
            $table->string('zipcode');
            $table->string('street');
            $table->string('country')->default('Philippines')->nullable();

            $table->string('business_registration_number');
            $table->string('tin_number');
            $table->string('currency_code')->default('PHP');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('user_id');
            $table->index('company_name');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
    }
};
