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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('asset_type')->nullable()->after('ai_categorized_category'); // 'asset', 'liability', or null for expense
            $table->decimal('asset_value', 15, 2)->nullable()->after('asset_type'); // Value if it's an asset
            $table->decimal('liability_amount', 15, 2)->nullable()->after('asset_value'); // Amount if it's a liability
            $table->decimal('monthly_payment', 12, 2)->nullable()->after('liability_amount'); // Monthly payment for liabilities
            $table->string('liability_type')->nullable()->after('monthly_payment'); // Type of liability (car_loan, mortgage, etc.)
            $table->decimal('interest_rate', 5, 2)->nullable()->after('liability_type'); // Interest rate for liabilities
            $table->date('due_date')->nullable()->after('interest_rate'); // Due date for liabilities
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'asset_type',
                'asset_value',
                'liability_amount',
                'monthly_payment',
                'liability_type',
                'interest_rate',
                'due_date'
            ]);
        });
    }
};
