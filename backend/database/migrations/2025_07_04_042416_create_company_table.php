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
            $table->id(); // Primary key: auto-increment

            $table->uuid('uuid')->unique()->index();


            // Company Details
            $table->string('company_name')->index();
            $table->string('display_name')->nullable();
            $table->string('company_logo', 512)->nullable();
            $table->string('business_type', 100);

            // Contact Information
            $table->string('telephone_contact_1', 50);
            $table->string('telephone_contact_2', 50)->nullable();
            $table->string('email_contact_1')->index();
            $table->string('email_contact_2')->nullable();

            // Address
            $table->string('street');
            $table->string('barangay');
            $table->string('city_municipality');
            $table->string('province');
            $table->string('region');
            $table->string('zipcode', 20);
            $table->string('country', 100)->default('Philippines');

            // Business Identifiers
            $table->string('business_registration_number');
            $table->string('tin_number');
            $table->char('currency_code', 3)->default('PHP');

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
